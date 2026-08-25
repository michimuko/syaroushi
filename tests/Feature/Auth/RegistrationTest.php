<?php

use App\Enums\UserRole;
use App\Models\BillingPlan;
use App\Models\Office;
use App\Models\PlatformAdmin;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered with the checkout-eligible billing plans', function () {
    // 初期マイグレーションで既にスターター/スタンダード/プロフェッショナル/エンタープライズが
    // 投入されているDB（エンタープライズのみstripe_price_idなし）を前提に、
    // 「Checkout可能なプランのみ絞り込まれ、そのうちスタンダードが初期選択される」ことを検証する。
    $standardPlan = BillingPlan::where('name', 'スタンダード')->sole();
    $ineligibleCount = BillingPlan::where(fn ($q) => $q->where('is_active', false)->orWhereNull('stripe_price_id'))->count();
    $eligibleCount = BillingPlan::query()->count() - $ineligibleCount;

    BillingPlan::factory()->create(['name' => '廃止プラン', 'is_active' => false, 'stripe_price_id' => 'price_old']);

    $response = $this->get('/register');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->has('billingPlans', $eligibleCount)
            ->where('selectedPlanId', $standardPlan->id));
});

test('new users can register and get a trial and plan assigned without registering a card', function () {
    $plan = BillingPlan::factory()->create(['stripe_price_id' => 'price_test_123']);

    $response = $this->post('/register', [
        'office_name' => 'テスト社労士事務所',
        'office_code' => 'test-sharoushi-office',
        'name' => 'Test User',
        'login_id' => 'test-user',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'billing_plan_id' => $plan->id,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = auth()->user();
    expect($user->role)->toBe(UserRole::Owner);
    expect($user->office->name)->toBe('テスト社労士事務所');
    expect($user->office->billing_plan_id)->toBe($plan->id);
    expect($user->office->stripe_customer_id)->toBeNull();
    expect($user->office->trial_ends_at)->not->toBeNull();
    expect($user->office->trial_ends_at->isFuture())->toBeTrue();
});

test('new users receive a verification email and cannot reach the dashboard until verified', function () {
    Notification::fake();
    $plan = BillingPlan::factory()->create(['stripe_price_id' => 'price_test_123']);

    $this->post('/register', [
        'office_name' => 'メール確認テスト事務所',
        'office_code' => 'verify-email-office',
        'name' => 'Verify User',
        'login_id' => 'verify-user',
        'email' => 'verify-user@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'billing_plan_id' => $plan->id,
    ]);

    $user = auth()->user();
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);

    $response = $this->get(route('dashboard', absolute: false));
    $response->assertRedirect(route('verification.notice', absolute: false));
});

test('office_code must be unique when registering', function () {
    $existingOffice = Office::factory()->create(['office_code' => 'taken-office-code']);
    $plan = BillingPlan::factory()->create(['stripe_price_id' => 'price_test_123']);

    $response = $this->post('/register', [
        'office_name' => '重複コードテスト事務所',
        'office_code' => 'taken-office-code',
        'name' => 'Test User 3',
        'login_id' => 'test-user-3',
        'email' => 'test3@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'billing_plan_id' => $plan->id,
    ]);

    $response->assertSessionHasErrors('office_code');
    expect(Office::where('name', '重複コードテスト事務所')->exists())->toBeFalse();
});

test('billing_plan_id is required when registering', function () {
    $response = $this->post('/register', [
        'office_name' => 'プラン未選択テスト事務所',
        'office_code' => 'no-plan-office',
        'name' => 'Test User',
        'login_id' => 'test-user-no-plan',
        'email' => 'no-plan@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('billing_plan_id');
    expect(Office::where('name', 'プラン未選択テスト事務所')->exists())->toBeFalse();
});

test('a plan without a Stripe price cannot be selected when registering', function () {
    $enterprisePlan = BillingPlan::factory()->create(['name' => 'エンタープライズ', 'stripe_price_id' => null]);

    $response = $this->post('/register', [
        'office_name' => '個別見積りプラン指定テスト事務所',
        'office_code' => 'enterprise-office',
        'name' => 'Test User',
        'login_id' => 'test-user-enterprise',
        'email' => 'enterprise@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'billing_plan_id' => $enterprisePlan->id,
    ]);

    $response->assertSessionHasErrors('billing_plan_id');
    expect(Office::where('name', '個別見積りプラン指定テスト事務所')->exists())->toBeFalse();
});

test('registering while a platform admin session is active in the same browser still assigns the new office correctly', function () {
    $admin = PlatformAdmin::factory()->create();
    $otherOffice = Office::factory()->create();
    $plan = BillingPlan::factory()->create(['stripe_price_id' => 'price_test_123']);

    $response = $this->actingAs($admin, 'platform')->post('/register', [
        'office_name' => 'もう一つのテスト事務所',
        'office_code' => 'another-test-office',
        'name' => 'Test User 2',
        'login_id' => 'test-user-2',
        'email' => 'test2@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'billing_plan_id' => $plan->id,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = auth()->user();
    $office = Office::where('name', 'もう一つのテスト事務所')->sole();
    expect($user->office_id)->toBe($office->id)
        ->and($user->office_id)->not->toBe($otherOffice->id);
});
