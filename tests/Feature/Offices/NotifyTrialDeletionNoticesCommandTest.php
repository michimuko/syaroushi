<?php

use App\Models\Office;
use App\Models\User;
use App\Notifications\TrialDeletionFinalNotice;
use App\Notifications\TrialDeletionWarning;
use App\Notifications\TrialEndedWithoutPayment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

test('it notifies the owner right after the trial ends and marks it notified', function () {
    Notification::fake();
    $office = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->subDays(2), 'stripe_subscription_id' => null]);
    $owner = User::factory()->for($office)->owner()->create();

    $this->artisan('offices:notify-deletion-notices')->assertExitCode(0);

    Notification::assertSentTo($owner, TrialEndedWithoutPayment::class);
    expect($office->fresh()->trial_ended_notified_at)->not->toBeNull();
});

test('it does not send the trial-ended notice twice', function () {
    Notification::fake();
    $office = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->subDays(2),
        'stripe_subscription_id' => null,
        'trial_ended_notified_at' => now(),
    ]);
    $owner = User::factory()->for($office)->owner()->create();

    $this->artisan('offices:notify-deletion-notices')->assertExitCode(0);

    Notification::assertNotSentTo($owner, TrialEndedWithoutPayment::class);
});

test('it sends the deletion warning once the 7-day warning period begins', function () {
    Notification::fake();
    $office = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->subDays(10),
        'stripe_subscription_id' => null,
        'trial_ended_notified_at' => now(),
    ]);
    $owner = User::factory()->for($office)->owner()->create();

    $this->artisan('offices:notify-deletion-notices')->assertExitCode(0);

    Notification::assertSentTo($owner, TrialDeletionWarning::class);
    expect($office->fresh()->deletion_warning_notified_at)->not->toBeNull();
});

test('it sends the final notice 7 days before the scheduled deletion date', function () {
    Notification::fake();
    // trial_ends_at + 53 days is past (60 - 7 = 53)
    $office = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->subDays(54),
        'stripe_subscription_id' => null,
        'trial_ended_notified_at' => now(),
        'deletion_warning_notified_at' => now(),
    ]);
    $owner = User::factory()->for($office)->owner()->create();

    $this->artisan('offices:notify-deletion-notices')->assertExitCode(0);

    Notification::assertSentTo($owner, TrialDeletionFinalNotice::class);
    expect($office->fresh()->deletion_final_notice_notified_at)->not->toBeNull();
});

test('a long-abandoned office receives all three notices in a single run', function () {
    Notification::fake();
    $office = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->subDays(90), 'stripe_subscription_id' => null]);
    $owner = User::factory()->for($office)->owner()->create();

    $this->artisan('offices:notify-deletion-notices')->assertExitCode(0);

    Notification::assertSentTo($owner, TrialEndedWithoutPayment::class);
    Notification::assertSentTo($owner, TrialDeletionWarning::class);
    Notification::assertSentTo($owner, TrialDeletionFinalNotice::class);
});

test('it does not notify offices still within their trial', function () {
    Notification::fake();
    $office = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->addDays(5), 'stripe_subscription_id' => null]);
    $owner = User::factory()->for($office)->owner()->create();

    $this->artisan('offices:notify-deletion-notices')->assertExitCode(0);

    Notification::assertNothingSentTo($owner);
});

test('it does not notify offices that have ever subscribed via Stripe, even past_due or canceled', function () {
    Notification::fake();
    $office = Office::factory()->create([
        'trial_ends_at' => CarbonImmutable::today()->subDays(90),
        'stripe_subscription_id' => 'sub_1',
        'stripe_subscription_status' => 'canceled',
    ]);
    $owner = User::factory()->for($office)->owner()->create();

    $this->artisan('offices:notify-deletion-notices')->assertExitCode(0);

    Notification::assertNothingSentTo($owner);
});

test('only owners are notified, not staff', function () {
    Notification::fake();
    $office = Office::factory()->create(['trial_ends_at' => CarbonImmutable::today()->subDays(2), 'stripe_subscription_id' => null]);
    $owner = User::factory()->for($office)->owner()->create();
    $staff = User::factory()->for($office)->create();

    $this->artisan('offices:notify-deletion-notices')->assertExitCode(0);

    Notification::assertSentTo($owner, TrialEndedWithoutPayment::class);
    Notification::assertNothingSentTo($staff);
});
