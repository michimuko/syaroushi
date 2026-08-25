<?php

use App\Models\Office;
use Carbon\CarbonImmutable;

test('billing_attention_reasons is empty for an office mid-trial with no plan assigned yet', function () {
    $office = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->addDays(20),
        'billing_plan_id' => null,
        'custom_monthly_price' => null,
    ]);

    expect($office->billing_attention_reasons)->toBe([]);
});

test('billing_attention_reasons flags no_plan once the trial has ended without a plan or custom price', function () {
    $office = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->subDay(),
        'billing_plan_id' => null,
        'custom_monthly_price' => null,
    ]);

    expect($office->billing_attention_reasons)->toContain('no_plan');
});

test('billing_attention_reasons does not flag no_plan once a custom price alone is set', function () {
    $office = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->subDay(),
        'billing_plan_id' => null,
        'custom_monthly_price' => 5000,
    ]);

    expect($office->billing_attention_reasons)->not->toContain('no_plan');
});

test('billing_attention_reasons flags trial_ending_soon within the 7-day window but not beyond it', function () {
    $soon = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->addDays(3)]);
    $notYet = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->addDays(10)]);

    expect($soon->billing_attention_reasons)->toContain('trial_ending_soon')
        ->and($notYet->billing_attention_reasons)->not->toContain('trial_ending_soon');
});

test('billing_attention_reasons flags payment_failed whenever stripe_payment_failed_at is set', function () {
    $office = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->addDays(20),
        'stripe_payment_failed_at' => now(),
    ]);

    expect($office->billing_attention_reasons)->toBe(['payment_failed']);
});

test('billing_attention_reasons can report multiple reasons at once', function () {
    $office = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->subDay(),
        'billing_plan_id' => null,
        'custom_monthly_price' => null,
        'stripe_payment_failed_at' => now(),
    ]);

    expect($office->billing_attention_reasons)->toBe(['payment_failed', 'no_plan']);
});

test('hasEverHadStripeSubscription stays true forever once a subscription id is assigned, even after cancellation', function () {
    $active = Office::factory()->create(['stripe_subscription_id' => 'sub_1', 'stripe_subscription_status' => 'active']);
    $pastDue = Office::factory()->create(['stripe_subscription_id' => 'sub_2', 'stripe_subscription_status' => 'past_due']);
    $canceled = Office::factory()->create(['stripe_subscription_id' => 'sub_3', 'stripe_subscription_status' => 'canceled']);
    $neverStarted = Office::factory()->create(['stripe_subscription_id' => null, 'stripe_subscription_status' => null]);

    expect($active->hasEverHadStripeSubscription())->toBeTrue()
        ->and($pastDue->hasEverHadStripeSubscription())->toBeTrue()
        ->and($canceled->hasEverHadStripeSubscription())->toBeTrue()
        ->and($neverStarted->hasEverHadStripeSubscription())->toBeFalse();
});

test('scheduledDeletionAt is trial_ends_at plus 60 days, or null without a trial', function () {
    $withTrial = Office::factory()->create(['trial_ends_at' => CarbonImmutable::parse('2026-01-01')]);
    $withoutTrial = Office::factory()->create(['trial_ends_at' => null]);

    expect($withTrial->scheduledDeletionAt()->toDateString())->toBe('2026-03-02')
        ->and($withoutTrial->scheduledDeletionAt())->toBeNull();
});

test('isPastTrialWithoutSubscription is true only once the trial has ended and Stripe was never used', function () {
    $midTrial = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->addDay(), 'stripe_subscription_id' => null]);
    $endedNoSub = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->subDay(), 'stripe_subscription_id' => null]);
    $endedWithSub = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->subDay(), 'stripe_subscription_id' => 'sub_1']);
    $noTrial = Office::factory()->create(['trial_ends_at' => null, 'stripe_subscription_id' => null]);

    expect($midTrial->isPastTrialWithoutSubscription())->toBeFalse()
        ->and($endedNoSub->isPastTrialWithoutSubscription())->toBeTrue()
        ->and($endedWithSub->isPastTrialWithoutSubscription())->toBeFalse()
        ->and($noTrial->isPastTrialWithoutSubscription())->toBeFalse();
});

test('isInDeletionWarningPeriod starts 7 days after trial end and isEligibleForDeletion starts 60 days after', function () {
    $justEnded = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->subDays(3), 'stripe_subscription_id' => null]);
    $warningPeriod = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->subDays(10), 'stripe_subscription_id' => null]);
    $eligible = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->subDays(61), 'stripe_subscription_id' => null]);

    expect($justEnded->isInDeletionWarningPeriod())->toBeFalse()
        ->and($justEnded->isEligibleForDeletion())->toBeFalse()
        ->and($warningPeriod->isInDeletionWarningPeriod())->toBeTrue()
        ->and($warningPeriod->isEligibleForDeletion())->toBeFalse()
        ->and($eligible->isInDeletionWarningPeriod())->toBeTrue()
        ->and($eligible->isEligibleForDeletion())->toBeTrue();
});

test('billing_attention_reasons flags pending_deletion once eligible for deletion', function () {
    $office = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->subDays(61),
        'billing_plan_id' => null,
        'custom_monthly_price' => null,
        'stripe_subscription_id' => null,
    ]);

    expect($office->billing_attention_reasons)->toContain('pending_deletion');
});

test('scopeNeedsBillingAttention matches the same offices billing_attention_reasons flags', function () {
    $flaggedForPaymentFailure = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->addDays(20),
        'stripe_payment_failed_at' => now(),
    ]);
    $flaggedForNoPlan = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->subDay(),
        'billing_plan_id' => null,
        'custom_monthly_price' => null,
    ]);
    $flaggedForTrialEndingSoon = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->addDays(2),
    ]);
    $flaggedForPendingDeletion = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->subDays(61),
        'custom_monthly_price' => 6800,
        'stripe_subscription_id' => null,
    ]);
    $healthyMidTrial = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->addDays(20),
    ]);
    $healthyWithPlan = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->subDay(),
        'custom_monthly_price' => 6800,
    ]);

    $matched = Office::query()->needsBillingAttention()->pluck('id')->all();

    expect($matched)->toContain($flaggedForPaymentFailure->id, $flaggedForNoPlan->id, $flaggedForTrialEndingSoon->id, $flaggedForPendingDeletion->id)
        ->not->toContain($healthyMidTrial->id, $healthyWithPlan->id);
});
