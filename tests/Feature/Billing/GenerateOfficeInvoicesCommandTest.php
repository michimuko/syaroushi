<?php

use App\Enums\ClientStatus;
use App\Models\BillingPlan;
use App\Models\Client;
use App\Models\Office;
use App\Models\OfficeInvoice;
use App\Models\User;
use Carbon\CarbonImmutable;

test('it generates an invoice using the assigned billing plan\'s monthly price and snapshots client/user counts', function () {
    $plan = BillingPlan::factory()->create(['name' => 'スターター', 'monthly_price' => 2800]);
    $office = Office::factory()->create(['is_active' => true, 'trial_ends_at' => null, 'billing_plan_id' => $plan->id]);
    Client::factory()->for($office)->count(4)->create(['status' => ClientStatus::Active]);
    Client::factory()->for($office)->create(['status' => ClientStatus::Inactive]);
    User::factory()->for($office)->count(2)->create();

    $this->artisan('billing:generate-invoices')->assertExitCode(0);

    $expectedPeriodStart = CarbonImmutable::today()->subMonthNoOverflow()->startOfMonth();
    $invoice = OfficeInvoice::where('office_id', $office->id)->sole();

    expect($invoice->period_start->toDateString())->toBe($expectedPeriodStart->toDateString())
        ->and($invoice->client_count)->toBe(4)
        ->and($invoice->user_count)->toBe(2)
        ->and($invoice->plan_name)->toBe('スターター')
        ->and($invoice->amount)->toBe(2800);
});

test('it skips offices still within their trial period', function () {
    $office = Office::factory()->create([
        'is_active' => true,
        'trial_ends_at' => CarbonImmutable::today()->addDays(5),
    ]);
    Client::factory()->for($office)->create(['status' => ClientStatus::Active]);

    $this->artisan('billing:generate-invoices')->assertExitCode(0);

    expect(OfficeInvoice::where('office_id', $office->id)->exists())->toBeFalse();
});

test('it skips inactive offices', function () {
    $office = Office::factory()->create(['is_active' => false, 'trial_ends_at' => null]);
    Client::factory()->for($office)->create(['status' => ClientStatus::Active]);

    $this->artisan('billing:generate-invoices')->assertExitCode(0);

    expect(OfficeInvoice::where('office_id', $office->id)->exists())->toBeFalse();
});

test('it prefers custom_monthly_price over the assigned plan\'s price', function () {
    $plan = BillingPlan::factory()->create(['name' => 'プロフェッショナル', 'monthly_price' => 14800]);
    $office = Office::factory()->create([
        'is_active' => true,
        'trial_ends_at' => null,
        'billing_plan_id' => $plan->id,
        'custom_monthly_price' => 20000,
    ]);
    Client::factory()->for($office)->count(2)->create(['status' => ClientStatus::Active]);

    $this->artisan('billing:generate-invoices')->assertExitCode(0);

    $invoice = OfficeInvoice::where('office_id', $office->id)->sole();
    expect($invoice->amount)->toBe(20000)
        ->and($invoice->plan_name)->toBe('プロフェッショナル');
});

test('it uses custom_monthly_price with a placeholder plan name when no plan is assigned', function () {
    $office = Office::factory()->create([
        'is_active' => true,
        'trial_ends_at' => null,
        'billing_plan_id' => null,
        'custom_monthly_price' => 3000,
    ]);
    Client::factory()->for($office)->create(['status' => ClientStatus::Active]);

    $this->artisan('billing:generate-invoices')->assertExitCode(0);

    $invoice = OfficeInvoice::where('office_id', $office->id)->sole();
    expect($invoice->amount)->toBe(3000)
        ->and($invoice->plan_name)->toBe('個別設定');
});

test('it skips offices with no determinable price without failing', function () {
    $office = Office::factory()->create([
        'is_active' => true,
        'trial_ends_at' => null,
        'billing_plan_id' => null,
        'custom_monthly_price' => null,
    ]);
    Client::factory()->for($office)->create(['status' => ClientStatus::Active]);

    $this->artisan('billing:generate-invoices')->assertExitCode(0);

    expect(OfficeInvoice::where('office_id', $office->id)->exists())->toBeFalse();
});

test('it skips offices with an active Stripe subscription (Stripe is the source of truth for those)', function () {
    $plan = BillingPlan::factory()->create(['monthly_price' => 14800]);
    $office = Office::factory()->create([
        'is_active' => true,
        'trial_ends_at' => null,
        'billing_plan_id' => $plan->id,
        'stripe_subscription_id' => 'sub_1',
        'stripe_subscription_status' => 'active',
    ]);
    Client::factory()->for($office)->create(['status' => ClientStatus::Active]);

    $this->artisan('billing:generate-invoices')->assertExitCode(0);

    expect(OfficeInvoice::where('office_id', $office->id)->exists())->toBeFalse();
});

test('it still skips offices whose Stripe subscription is past_due (Stripe already invoiced them, avoids a duplicate DB invoice)', function () {
    $plan = BillingPlan::factory()->create(['monthly_price' => 14800]);
    $office = Office::factory()->create([
        'is_active' => true,
        'trial_ends_at' => null,
        'billing_plan_id' => $plan->id,
        'stripe_subscription_id' => 'sub_1',
        'stripe_subscription_status' => 'past_due',
    ]);
    Client::factory()->for($office)->create(['status' => ClientStatus::Active]);

    $this->artisan('billing:generate-invoices')->assertExitCode(0);

    expect(OfficeInvoice::where('office_id', $office->id)->exists())->toBeFalse();
});

test('it never resumes generating DB invoices for an office that was ever Stripe-managed, even after cancellation', function () {
    $plan = BillingPlan::factory()->create(['name' => 'スタンダード', 'monthly_price' => 6800]);
    $office = Office::factory()->create([
        'is_active' => true,
        'trial_ends_at' => null,
        'billing_plan_id' => $plan->id,
        'stripe_subscription_id' => 'sub_1',
        'stripe_subscription_status' => 'canceled',
    ]);
    Client::factory()->for($office)->create(['status' => ClientStatus::Active]);

    $this->artisan('billing:generate-invoices')->assertExitCode(0);

    expect(OfficeInvoice::where('office_id', $office->id)->exists())->toBeFalse();
});

test('running it twice does not duplicate invoices for the same period', function () {
    $plan = BillingPlan::factory()->create();
    $office = Office::factory()->create(['is_active' => true, 'trial_ends_at' => null, 'billing_plan_id' => $plan->id]);
    Client::factory()->for($office)->create(['status' => ClientStatus::Active]);

    $this->artisan('billing:generate-invoices')->assertExitCode(0);
    $this->artisan('billing:generate-invoices')->assertExitCode(0);

    expect(OfficeInvoice::where('office_id', $office->id)->count())->toBe(1);
});
