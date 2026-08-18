<?php

use App\Models\BillingPlan;
use App\Models\Office;
use App\Models\User;
use App\Services\Stripe\StripeSubscriptionGateway;

test('an owner starting checkout for the first time creates a Stripe customer and redirects to Checkout', function () {
    $plan = BillingPlan::factory()->create(['stripe_price_id' => 'price_test_123']);
    $office = Office::factory()->create(['billing_plan_id' => $plan->id, 'stripe_customer_id' => null]);
    $owner = User::factory()->for($office)->owner()->create();

    $this->mock(StripeSubscriptionGateway::class, function ($mock) {
        $mock->shouldReceive('createCustomer')->once()->andReturn('cus_test_123');
        $mock->shouldReceive('createCheckoutSessionUrl')->once()
            ->with('cus_test_123', 'price_test_123', Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn('https://checkout.stripe.com/test-session');
    });

    $response = $this->actingAs($owner)
        ->withHeaders(['X-Inertia' => 'true'])
        ->post(route('settings.billing.checkout'));

    $response->assertStatus(409)
        ->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/test-session');

    expect($office->refresh()->stripe_customer_id)->toBe('cus_test_123');
});

test('checkout reuses an existing Stripe customer id without creating a new one', function () {
    $plan = BillingPlan::factory()->create(['stripe_price_id' => 'price_test_123']);
    $office = Office::factory()->create(['billing_plan_id' => $plan->id, 'stripe_customer_id' => 'cus_existing']);
    $owner = User::factory()->for($office)->owner()->create();

    $this->mock(StripeSubscriptionGateway::class, function ($mock) {
        $mock->shouldReceive('createCustomer')->never();
        $mock->shouldReceive('createCheckoutSessionUrl')->once()
            ->with('cus_existing', 'price_test_123', Mockery::any(), Mockery::any(), Mockery::any())
            ->andReturn('https://checkout.stripe.com/test-session');
    });

    $this->actingAs($owner)
        ->withHeaders(['X-Inertia' => 'true'])
        ->post(route('settings.billing.checkout'))
        ->assertStatus(409);
});

test('checkout is rejected with an error when the plan has no Stripe price configured', function () {
    $plan = BillingPlan::factory()->create(['stripe_price_id' => null]);
    $office = Office::factory()->create(['billing_plan_id' => $plan->id]);
    $owner = User::factory()->for($office)->owner()->create();

    $this->mock(StripeSubscriptionGateway::class, function ($mock) {
        $mock->shouldNotReceive('createCustomer');
        $mock->shouldNotReceive('createCheckoutSessionUrl');
    });

    $response = $this->actingAs($owner)->post(route('settings.billing.checkout'));

    $response->assertRedirect();
    expect(session('error'))->not->toBeNull();
});

test('checkout is rejected with an error when the office already has an active Stripe subscription', function () {
    $plan = BillingPlan::factory()->create(['stripe_price_id' => 'price_test_123']);
    $office = Office::factory()->create([
        'billing_plan_id' => $plan->id,
        'stripe_customer_id' => 'cus_existing',
        'stripe_subscription_status' => 'active',
    ]);
    $owner = User::factory()->for($office)->owner()->create();

    $this->mock(StripeSubscriptionGateway::class, function ($mock) {
        $mock->shouldNotReceive('createCustomer');
        $mock->shouldNotReceive('createCheckoutSessionUrl');
    });

    $response = $this->actingAs($owner)->post(route('settings.billing.checkout'));

    $response->assertRedirect();
    expect(session('error'))->not->toBeNull();
});

test('a staff member cannot start checkout', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();

    $this->actingAs($staff)->post(route('settings.billing.checkout'))->assertForbidden();
});

test('the customer portal redirects to Stripe when a customer already exists', function () {
    $office = Office::factory()->create(['stripe_customer_id' => 'cus_existing']);
    $owner = User::factory()->for($office)->owner()->create();

    $this->mock(StripeSubscriptionGateway::class, function ($mock) {
        $mock->shouldReceive('createPortalSessionUrl')->once()
            ->with('cus_existing', Mockery::any())
            ->andReturn('https://billing.stripe.com/test-portal');
    });

    // Inertiaのバージョン一致判定を経由しない素のブラウザ遷移として検証する
    // （X-Inertiaヘッダ付きでのX-Inertia-Location検証はcheckout側のPOSTテストで確認済み）
    $response = $this->actingAs($owner)->get(route('settings.billing.portal'));

    $response->assertRedirect('https://billing.stripe.com/test-portal');
});

test('the customer portal is rejected with an error when checkout has never been started', function () {
    $office = Office::factory()->create(['stripe_customer_id' => null]);
    $owner = User::factory()->for($office)->owner()->create();

    $this->mock(StripeSubscriptionGateway::class, function ($mock) {
        $mock->shouldNotReceive('createPortalSessionUrl');
    });

    $response = $this->actingAs($owner)->get(route('settings.billing.portal'));

    $response->assertRedirect();
    expect(session('error'))->not->toBeNull();
});
