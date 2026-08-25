<?php

use App\Models\Office;
use App\Models\PlatformAdmin;
use App\Models\User;

test('a request logs out and redirects when the office was soft-deleted mid-session', function () {
    $office = Office::factory()->create(['is_active' => true]);
    $user = User::factory()->for($office)->owner()->create();

    $this->actingAs($user);
    $office->delete();

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
    expect(session('error'))->not->toBeNull();
});

test('a request logs out and redirects when the office was deactivated mid-session', function () {
    $office = Office::factory()->create(['is_active' => true]);
    $user = User::factory()->for($office)->owner()->create();

    $this->actingAs($user);
    $office->update(['is_active' => false]);

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('requests pass through normally for an active office', function () {
    $office = Office::factory()->create(['is_active' => true]);
    $user = User::factory()->for($office)->owner()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});

test('a platform admin session is unaffected (web guard has no user)', function () {
    $admin = PlatformAdmin::factory()->create();

    $response = $this->actingAs($admin, 'platform')->get(route('platform.offices.index'));

    $response->assertOk();
});
