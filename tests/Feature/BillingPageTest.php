<?php

use App\Enums\ClientStatus;
use App\Models\BillingPlan;
use App\Models\Client;
use App\Models\Office;
use App\Models\OfficeInvoice;
use App\Models\User;

test('an owner can view their office\'s billing status and estimated monthly amount', function () {
    $plan = BillingPlan::factory()->create(['name' => 'スタンダード', 'monthly_price' => 14800]);
    $office = Office::factory()->create(['trial_ends_at' => null, 'billing_plan_id' => $plan->id]);
    $owner = User::factory()->for($office)->owner()->create();

    Client::factory()->for($office)->count(3)->create(['status' => ClientStatus::Active]);
    Client::factory()->for($office)->create(['status' => ClientStatus::Inactive]);

    $response = $this->actingAs($owner)->get(route('settings.billing.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentClientCount', 3)
            ->where('estimatedMonthlyAmount', 14800)
            ->where('billingPlan.name', 'スタンダード')
            ->where('office.is_trial_active', false)
        );
});

test('custom_monthly_price overrides the assigned plan\'s price in the estimate', function () {
    $plan = BillingPlan::factory()->create(['monthly_price' => 14800]);
    $office = Office::factory()->create([
        'trial_ends_at' => null,
        'billing_plan_id' => $plan->id,
        'custom_monthly_price' => 9800,
    ]);
    $owner = User::factory()->for($office)->owner()->create();

    $response = $this->actingAs($owner)->get(route('settings.billing.index'));

    $response->assertInertia(fn ($page) => $page->where('estimatedMonthlyAmount', 9800));
});

test('estimatedMonthlyAmount is null when no plan or custom price is set', function () {
    $office = Office::factory()->create([
        'trial_ends_at' => null,
        'billing_plan_id' => null,
        'custom_monthly_price' => null,
    ]);
    $owner = User::factory()->for($office)->owner()->create();

    $response = $this->actingAs($owner)->get(route('settings.billing.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->where('estimatedMonthlyAmount', null));
});

test('exceededLimits reflects usage over the plan\'s limits', function () {
    $plan = BillingPlan::factory()->create(['max_clients' => 1]);
    $office = Office::factory()->create(['trial_ends_at' => null, 'billing_plan_id' => $plan->id]);
    $owner = User::factory()->for($office)->owner()->create();
    Client::factory()->for($office)->count(2)->create(['status' => ClientStatus::Active]);

    $response = $this->actingAs($owner)->get(route('settings.billing.index'));

    $response->assertInertia(fn ($page) => $page->where('exceededLimits', ['clients']));
});

test('a staff member cannot view the office\'s billing page', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();

    $this->actingAs($staff)->get(route('settings.billing.index'))->assertForbidden();
});

test('an owner only sees their own office\'s invoice history', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();
    $ownerA = User::factory()->for($officeA)->owner()->create();

    OfficeInvoice::factory()->for($officeA)->create(['amount' => 1000]);
    OfficeInvoice::factory()->for($officeB)->create(['amount' => 9999]);

    $response = $this->actingAs($ownerA)->get(route('settings.billing.index'));

    $response->assertInertia(fn ($page) => $page
        ->has('invoices.data', 1)
        ->where('invoices.data.0.amount', 1000)
    );
});
