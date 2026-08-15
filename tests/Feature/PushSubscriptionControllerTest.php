<?php

use App\Models\Office;
use App\Models\PushSubscription;
use App\Models\User;

it('requires authentication to subscribe', function () {
    $response = $this->postJson(route('push-subscriptions.store'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
    ]);

    $response->assertUnauthorized();
});

it('saves a push subscription for the authenticated user with the office_id set', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $response = $this->actingAs($user)->postJson(route('push-subscriptions.store'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
        'public_key' => 'public-key',
        'auth_token' => 'auth-token',
        'content_encoding' => 'aes128gcm',
    ]);

    $response->assertOk();

    $subscription = PushSubscription::query()->sole();
    expect($subscription->office_id)->toBe($office->id)
        ->and($subscription->subscribable_id)->toBe($user->id)
        ->and($subscription->subscribable_type)->toBe(User::class)
        ->and($subscription->endpoint)->toBe('https://fcm.googleapis.com/fcm/send/abc');
});

it('deletes a push subscription for the authenticated user', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $user->updatePushSubscription('https://fcm.googleapis.com/fcm/send/abc', 'key', 'token');

    $response = $this->actingAs($user)->deleteJson(route('push-subscriptions.destroy'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
    ]);

    $response->assertOk();
    expect(PushSubscription::query()->count())->toBe(0);
});

it('does not let a user delete another user\'s subscription by guessing the endpoint', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->create();
    $owner->updatePushSubscription('https://fcm.googleapis.com/fcm/send/abc', 'key', 'token');

    $otherOffice = Office::factory()->create();
    $otherUser = User::factory()->for($otherOffice)->create();

    $this->actingAs($otherUser)->deleteJson(route('push-subscriptions.destroy'), [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
    ]);

    expect(PushSubscription::query()->count())->toBe(1);
});
