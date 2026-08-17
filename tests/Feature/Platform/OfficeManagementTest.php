<?php

use App\Models\BillingPlan;
use App\Models\Office;
use App\Models\PlatformAdmin;
use App\Models\User;

test('a platform admin can create an office with its first owner in one go', function () {
    $admin = PlatformAdmin::factory()->create();

    $response = $this->actingAs($admin, 'platform')->post(route('platform.offices.store'), [
        'office_name' => '新宿社会保険労務士事務所',
        'contract_plan' => 'standard',
        'owner_name' => '新規オーナー',
        'owner_email' => 'new-owner@example.com',
        'owner_password' => 'password123',
        'owner_password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('platform.offices.index'));

    $office = Office::where('name', '新宿社会保険労務士事務所')->sole();
    expect($office->is_active)->toBeTrue();

    $owner = User::where('email', 'new-owner@example.com')->sole();
    expect($owner->office_id)->toBe($office->id)
        ->and($owner->role->value)->toBe('owner');
});

test('office creation rolls back entirely if the owner email is already taken', function () {
    $admin = PlatformAdmin::factory()->create();
    $existingOffice = Office::factory()->create();
    User::factory()->for($existingOffice)->create(['email' => 'taken@example.com']);

    $response = $this->actingAs($admin, 'platform')->post(route('platform.offices.store'), [
        'office_name' => '重複テスト事務所',
        'owner_name' => 'テスト',
        'owner_email' => 'taken@example.com',
        'owner_password' => 'password123',
        'owner_password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('owner_email');
    expect(Office::where('name', '重複テスト事務所')->exists())->toBeFalse();
});

test('a platform admin can toggle an office\'s is_active flag', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create();

    $response = $this->actingAs($admin, 'platform')->put(route('platform.offices.update', $office), [
        'name' => $office->name,
        'contract_plan' => $office->contract_plan,
        'is_active' => false,
    ]);

    $response->assertRedirect(route('platform.offices.index'));
    expect($office->fresh()->is_active)->toBeFalse();
});

test('a platform admin can assign a billing plan and a custom monthly price to an office', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create();
    $plan = BillingPlan::factory()->create();

    $response = $this->actingAs($admin, 'platform')->put(route('platform.offices.update', $office), [
        'name' => $office->name,
        'contract_plan' => $office->contract_plan,
        'is_active' => true,
        'billing_plan_id' => $plan->id,
        'custom_monthly_price' => 350,
    ]);

    $response->assertRedirect(route('platform.offices.index'));
    $office->refresh();
    expect($office->billing_plan_id)->toBe($plan->id)
        ->and($office->custom_monthly_price)->toBe(350);
});

test('a platform admin can clear an office back to an unassigned plan and default pricing', function () {
    $admin = PlatformAdmin::factory()->create();
    $plan = BillingPlan::factory()->create();
    $office = Office::factory()->create([
        'billing_plan_id' => $plan->id,
        'custom_monthly_price' => 350,
    ]);

    $response = $this->actingAs($admin, 'platform')->put(route('platform.offices.update', $office), [
        'name' => $office->name,
        'contract_plan' => $office->contract_plan,
        'is_active' => true,
        'billing_plan_id' => null,
        'custom_monthly_price' => null,
    ]);

    $response->assertRedirect(route('platform.offices.index'));
    $office->refresh();
    expect($office->billing_plan_id)->toBeNull()
        ->and($office->custom_monthly_price)->toBeNull();
});
