<?php

use App\Models\BillingPlan;
use App\Models\Office;
use App\Models\PlatformAdmin;
use App\Models\User;

test('a platform admin can view the billing plan list', function () {
    $admin = PlatformAdmin::factory()->create();
    BillingPlan::factory()->create(['name' => 'テスト用プラン']);

    $response = $this->actingAs($admin, 'platform')->get(route('platform.billing-plans.index'));

    $response->assertOk()->assertInertia(fn ($page) => $page->component('Platform/BillingPlans/Index'));

    $props = $response->viewData('page')['props'];
    expect(collect($props['billingPlans'])->pluck('name'))->toContain('テスト用プラン');
});

test('a platform admin can create a plan', function () {
    $admin = PlatformAdmin::factory()->create();

    $response = $this->actingAs($admin, 'platform')->post(route('platform.billing-plans.store'), [
        'name' => 'カスタムプラン',
        'max_clients' => 30,
        'max_users' => 5,
        'monthly_price' => 9800,
        'sort_order' => 10,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('platform.billing-plans.index'));
    $plan = BillingPlan::where('name', 'カスタムプラン')->sole();
    expect($plan->max_clients)->toBe(30)
        ->and($plan->max_users)->toBe(5)
        ->and($plan->monthly_price)->toBe(9800)
        ->and($plan->is_active)->toBeTrue();
});

test('a platform admin can create a fully unlimited custom-quote plan', function () {
    $admin = PlatformAdmin::factory()->create();

    $response = $this->actingAs($admin, 'platform')->post(route('platform.billing-plans.store'), [
        'name' => 'エンタープライズ2',
        'max_clients' => null,
        'max_users' => null,
        'monthly_price' => null,
        'sort_order' => 99,
        'is_active' => true,
    ]);

    $response->assertRedirect();
    $plan = BillingPlan::where('name', 'エンタープライズ2')->sole();
    expect($plan->max_clients)->toBeNull()
        ->and($plan->max_users)->toBeNull()
        ->and($plan->monthly_price)->toBeNull();
});

test('a platform admin can update a plan', function () {
    $admin = PlatformAdmin::factory()->create();
    $plan = BillingPlan::factory()->create(['name' => '旧名称', 'monthly_price' => 10000]);

    $response = $this->actingAs($admin, 'platform')->put(route('platform.billing-plans.update', $plan), [
        'name' => '新名称',
        'max_clients' => 40,
        'max_users' => 8,
        'monthly_price' => 12000,
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('platform.billing-plans.index'));
    $plan->refresh();
    expect($plan->name)->toBe('新名称')
        ->and($plan->monthly_price)->toBe(12000);
});

test('a platform admin can deactivate a plan and it still appears in the list', function () {
    $admin = PlatformAdmin::factory()->create();
    $plan = BillingPlan::factory()->create(['is_active' => true]);

    $this->actingAs($admin, 'platform')->put(route('platform.billing-plans.update', $plan), [
        'name' => $plan->name,
        'max_clients' => $plan->max_clients,
        'max_users' => $plan->max_users,
        'monthly_price' => $plan->monthly_price,
        'sort_order' => $plan->sort_order,
        'is_active' => false,
    ]);

    expect($plan->fresh()->is_active)->toBeFalse();

    $response = $this->actingAs($admin, 'platform')->get(route('platform.billing-plans.index'));
    $props = $response->viewData('page')['props'];
    expect(collect($props['billingPlans'])->pluck('id'))->toContain($plan->id);
});

test('creating a plan requires a name', function () {
    $admin = PlatformAdmin::factory()->create();

    $response = $this->actingAs($admin, 'platform')->post(route('platform.billing-plans.store'), [
        'name' => '',
        'sort_order' => 0,
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors('name');
});

test('a non platform admin cannot access billing plan management', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $response = $this->actingAs($user)->get(route('platform.billing-plans.index'));

    $response->assertRedirect(route('platform.login'));
});
