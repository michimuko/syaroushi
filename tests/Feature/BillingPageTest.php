<?php

use App\Enums\ClientStatus;
use App\Models\BillingSetting;
use App\Models\Client;
use App\Models\Office;
use App\Models\OfficeInvoice;
use App\Models\User;

test('an owner can view their office\'s billing status and estimated monthly amount', function () {
    $office = Office::factory()->create(['trial_ends_at' => null]);
    $owner = User::factory()->for($office)->owner()->create();
    BillingSetting::current()->update(['unit_price_per_client' => 500]);

    Client::factory()->for($office)->count(3)->create(['status' => ClientStatus::Active]);
    Client::factory()->for($office)->create(['status' => ClientStatus::Inactive]);

    $response = $this->actingAs($owner)->get(route('settings.billing.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentClientCount', 3)
            ->where('estimatedMonthlyAmount', 1500)
            ->where('office.is_trial_active', false)
        );
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
