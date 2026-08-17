<?php

use App\Models\BillingSetting;
use App\Models\Office;
use App\Models\PlatformAdmin;

test('a platform admin can update the platform-wide billing settings', function () {
    $admin = PlatformAdmin::factory()->create();

    $response = $this->actingAs($admin, 'platform')->put(route('platform.billing-settings.update'), [
        'trial_days' => 14,
        'billing_cycle' => 'monthly',
    ]);

    $response->assertRedirect(route('platform.billing-plans.index'));

    $setting = BillingSetting::current();
    expect($setting->trial_days)->toBe(14)
        ->and($setting->billing_cycle->value)->toBe('monthly');
});

test('billing settings default to the seeded values', function () {
    $setting = BillingSetting::current();

    expect($setting->trial_days)->toBe(30);
});

test('creating an office via the platform sets trial_ends_at based on the configured trial days', function () {
    $admin = PlatformAdmin::factory()->create();
    BillingSetting::current()->update(['trial_days' => 45]);

    $this->actingAs($admin, 'platform')->post(route('platform.offices.store'), [
        'office_name' => 'トライアル事務所',
        'owner_name' => 'テストオーナー',
        'owner_email' => 'trial-owner@example.com',
        'owner_password' => 'password123',
        'owner_password_confirmation' => 'password123',
    ]);

    $office = Office::where('name', 'トライアル事務所')->sole();
    expect($office->trial_ends_at->toDateString())->toBe(now()->addDays(45)->toDateString())
        ->and($office->isTrialActive())->toBeTrue();
});

test('a platform admin can extend or clear an office\'s trial end date', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create(['trial_ends_at' => now()->addDays(5)]);

    $response = $this->actingAs($admin, 'platform')->put(route('platform.offices.update', $office), [
        'name' => $office->name,
        'contract_plan' => $office->contract_plan,
        'is_active' => true,
        'trial_ends_at' => null,
    ]);

    $response->assertRedirect(route('platform.offices.index'));
    expect($office->fresh()->trial_ends_at)->toBeNull();
});
