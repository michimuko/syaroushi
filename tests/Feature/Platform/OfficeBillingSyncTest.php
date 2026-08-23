<?php

use App\Models\BillingPlan;
use App\Models\Office;
use App\Models\PlatformAdmin;
use App\Services\Stripe\StripeSubscriptionGateway;

test('confirming billing for an office without an active Stripe subscription is rejected without calling Stripe', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create([
        'stripe_subscription_id' => null,
        'stripe_subscription_status' => null,
    ]);

    $this->mock(StripeSubscriptionGateway::class, function ($mock) {
        $mock->shouldNotReceive('syncSubscriptionPrice');
        $mock->shouldNotReceive('productIdForPrice');
    });

    $response = $this->actingAs($admin, 'platform')->post(route('platform.offices.sync-billing', $office));

    $response->assertRedirect();
    expect(session('error'))->not->toBeNull();
});

test('confirming billing uses the plan\'s Stripe price directly when no custom price is set', function () {
    $admin = PlatformAdmin::factory()->create();
    $plan = BillingPlan::factory()->create(['stripe_price_id' => 'price_plan_123', 'monthly_price' => 6800]);
    $office = Office::factory()->create([
        'billing_plan_id' => $plan->id,
        'custom_monthly_price' => null,
        'stripe_subscription_id' => 'sub_123',
        'stripe_subscription_status' => 'active',
    ]);

    $this->mock(StripeSubscriptionGateway::class, function ($mock) {
        $mock->shouldNotReceive('productIdForPrice');
        $mock->shouldReceive('syncSubscriptionPrice')->once()
            ->with('sub_123', 'price_plan_123', null, null, Mockery::any())
            ->andReturn(['invoice_id' => 'in_1', 'invoice_status' => 'paid', 'amount_paid' => 6800]);
    });

    $response = $this->actingAs($admin, 'platform')->post(route('platform.offices.sync-billing', $office));

    $response->assertRedirect();
    expect(session('success'))->toContain('6,800')->and(session('error'))->toBeNull();
});

test('confirming billing uses a dynamic price built from the plan\'s product when a custom monthly price is set', function () {
    $admin = PlatformAdmin::factory()->create();
    $plan = BillingPlan::factory()->create(['stripe_price_id' => 'price_plan_123', 'monthly_price' => 6800]);
    $office = Office::factory()->create([
        'billing_plan_id' => $plan->id,
        'custom_monthly_price' => 5000,
        'stripe_subscription_id' => 'sub_123',
        'stripe_subscription_status' => 'active',
    ]);

    $this->mock(StripeSubscriptionGateway::class, function ($mock) {
        $mock->shouldReceive('productIdForPrice')->once()
            ->with('price_plan_123')
            ->andReturn('prod_abc');
        $mock->shouldReceive('syncSubscriptionPrice')->once()
            ->with('sub_123', null, 5000, 'prod_abc', Mockery::any())
            ->andReturn(['invoice_id' => 'in_1', 'invoice_status' => 'paid', 'amount_paid' => 5000]);
    });

    $response = $this->actingAs($admin, 'platform')->post(route('platform.offices.sync-billing', $office));

    $response->assertRedirect();
    expect(session('success'))->toContain('5,000');
});

test('confirming billing is rejected when neither a custom price nor a plan amount is available', function () {
    $admin = PlatformAdmin::factory()->create();
    $office = Office::factory()->create([
        'billing_plan_id' => null,
        'custom_monthly_price' => null,
        'stripe_subscription_id' => 'sub_123',
        'stripe_subscription_status' => 'active',
    ]);

    $this->mock(StripeSubscriptionGateway::class, function ($mock) {
        $mock->shouldNotReceive('syncSubscriptionPrice');
    });

    $response = $this->actingAs($admin, 'platform')->post(route('platform.offices.sync-billing', $office));

    $response->assertRedirect();
    expect(session('error'))->not->toBeNull();
});

test('confirming billing is rejected when the assigned plan has no Stripe price and no custom price is set', function () {
    $admin = PlatformAdmin::factory()->create();
    $plan = BillingPlan::factory()->create(['stripe_price_id' => null, 'monthly_price' => null]);
    $office = Office::factory()->create([
        'billing_plan_id' => $plan->id,
        'custom_monthly_price' => 4800,
        'stripe_subscription_id' => 'sub_123',
        'stripe_subscription_status' => 'active',
    ]);

    $this->mock(StripeSubscriptionGateway::class, function ($mock) {
        $mock->shouldReceive('productIdForPrice')->never();
        $mock->shouldReceive('syncSubscriptionPrice')->once()
            ->with('sub_123', null, 4800, null, Mockery::any())
            ->andReturn(['invoice_id' => 'in_1', 'invoice_status' => 'paid', 'amount_paid' => 4800]);
    });

    $response = $this->actingAs($admin, 'platform')->post(route('platform.offices.sync-billing', $office));

    $response->assertRedirect();
    expect(session('success'))->toContain('4,800');
});

test('a non-paid invoice status after syncing surfaces as an error, not a success', function () {
    $admin = PlatformAdmin::factory()->create();
    $plan = BillingPlan::factory()->create(['stripe_price_id' => 'price_plan_123', 'monthly_price' => 6800]);
    $office = Office::factory()->create([
        'billing_plan_id' => $plan->id,
        'custom_monthly_price' => null,
        'stripe_subscription_id' => 'sub_123',
        'stripe_subscription_status' => 'active',
    ]);

    $this->mock(StripeSubscriptionGateway::class, function ($mock) {
        $mock->shouldReceive('syncSubscriptionPrice')->once()
            ->andReturn(['invoice_id' => 'in_1', 'invoice_status' => 'open', 'amount_paid' => 0]);
    });

    $response = $this->actingAs($admin, 'platform')->post(route('platform.offices.sync-billing', $office));

    $response->assertRedirect();
    expect(session('error'))->not->toBeNull()->and(session('success'))->toBeNull();
});

test('a Stripe API failure while syncing surfaces as an error instead of a 500', function () {
    $admin = PlatformAdmin::factory()->create();
    $plan = BillingPlan::factory()->create(['stripe_price_id' => 'price_plan_123', 'monthly_price' => 6800]);
    $office = Office::factory()->create([
        'billing_plan_id' => $plan->id,
        'custom_monthly_price' => null,
        'stripe_subscription_id' => 'sub_123',
        'stripe_subscription_status' => 'active',
    ]);

    $this->mock(StripeSubscriptionGateway::class, function ($mock) {
        $mock->shouldReceive('syncSubscriptionPrice')->once()
            ->andThrow(new Exception('stripe unreachable'));
    });

    $response = $this->actingAs($admin, 'platform')->post(route('platform.offices.sync-billing', $office));

    $response->assertRedirect();
    expect(session('error'))->toContain('stripe unreachable');
});
