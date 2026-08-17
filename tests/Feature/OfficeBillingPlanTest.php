<?php

use App\Enums\ClientStatus;
use App\Models\BillingPlan;
use App\Models\Client;
use App\Models\Office;
use App\Models\User;

test('exceedsPlanLimits returns empty when no plan is assigned', function () {
    $office = Office::factory()->create(['billing_plan_id' => null]);

    expect($office->exceedsPlanLimits())->toBe([]);
});

test('exceedsPlanLimits returns empty when usage is within limits', function () {
    $plan = BillingPlan::factory()->create(['max_clients' => 5, 'max_users' => 5]);
    $office = Office::factory()->create(['billing_plan_id' => $plan->id]);
    Client::factory()->for($office)->count(3)->create(['status' => ClientStatus::Active]);
    User::factory()->for($office)->count(3)->create();

    expect($office->exceedsPlanLimits())->toBe([]);
});

test('exceedsPlanLimits reports only clients when only client count is exceeded', function () {
    $plan = BillingPlan::factory()->create(['max_clients' => 2, 'max_users' => 5]);
    $office = Office::factory()->create(['billing_plan_id' => $plan->id]);
    Client::factory()->for($office)->count(3)->create(['status' => ClientStatus::Active]);
    User::factory()->for($office)->count(3)->create();

    expect($office->exceedsPlanLimits())->toBe(['clients']);
});

test('exceedsPlanLimits reports only users when only user count is exceeded', function () {
    $plan = BillingPlan::factory()->create(['max_clients' => 5, 'max_users' => 2]);
    $office = Office::factory()->create(['billing_plan_id' => $plan->id]);
    Client::factory()->for($office)->count(3)->create(['status' => ClientStatus::Active]);
    User::factory()->for($office)->count(3)->create();

    expect($office->exceedsPlanLimits())->toBe(['users']);
});

test('exceedsPlanLimits reports both when both limits are exceeded', function () {
    $plan = BillingPlan::factory()->create(['max_clients' => 1, 'max_users' => 1]);
    $office = Office::factory()->create(['billing_plan_id' => $plan->id]);
    Client::factory()->for($office)->count(3)->create(['status' => ClientStatus::Active]);
    User::factory()->for($office)->count(3)->create();

    expect($office->exceedsPlanLimits())->toEqualCanonicalizing(['clients', 'users']);
});

test('exceedsPlanLimits treats a null limit as unlimited regardless of usage', function () {
    $plan = BillingPlan::factory()->create(['max_clients' => null, 'max_users' => null]);
    $office = Office::factory()->create(['billing_plan_id' => $plan->id]);
    Client::factory()->for($office)->count(50)->create(['status' => ClientStatus::Active]);
    User::factory()->for($office)->count(50)->create();

    expect($office->exceedsPlanLimits())->toBe([]);
});

test('currentUserCount counts related users', function () {
    $office = Office::factory()->create();
    User::factory()->for($office)->count(4)->create();

    expect($office->currentUserCount())->toBe(4);
});
