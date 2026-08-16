<?php

use App\Enums\ClientStatus;
use App\Models\BillingSetting;
use App\Models\Client;
use App\Models\Office;
use App\Models\OfficeInvoice;
use Carbon\CarbonImmutable;

test('it generates an invoice for the previous month based on active client count', function () {
    BillingSetting::current()->update(['unit_price_per_client' => 500]);
    $office = Office::factory()->create(['is_active' => true, 'trial_ends_at' => null]);
    Client::factory()->for($office)->count(4)->create(['status' => ClientStatus::Active]);
    Client::factory()->for($office)->create(['status' => ClientStatus::Inactive]);

    $this->artisan('billing:generate-invoices')->assertExitCode(0);

    $expectedPeriodStart = CarbonImmutable::today()->subMonthNoOverflow()->startOfMonth();
    $invoice = OfficeInvoice::where('office_id', $office->id)->sole();

    expect($invoice->period_start->toDateString())->toBe($expectedPeriodStart->toDateString())
        ->and($invoice->client_count)->toBe(4)
        ->and($invoice->unit_price)->toBe(500)
        ->and($invoice->amount)->toBe(2000);
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

test('running it twice does not duplicate invoices for the same period', function () {
    $office = Office::factory()->create(['is_active' => true, 'trial_ends_at' => null]);
    Client::factory()->for($office)->create(['status' => ClientStatus::Active]);

    $this->artisan('billing:generate-invoices')->assertExitCode(0);
    $this->artisan('billing:generate-invoices')->assertExitCode(0);

    expect(OfficeInvoice::where('office_id', $office->id)->count())->toBe(1);
});
