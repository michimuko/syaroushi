<?php

use App\Models\Office;
use App\Models\PlatformAdmin;
use App\Models\User;

test('platform login screen can be rendered', function () {
    $response = $this->get(route('platform.login'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Platform/Auth/Login'));
});

test('a platform admin can log in with correct credentials', function () {
    $admin = PlatformAdmin::factory()->create();

    $response = $this->post(route('platform.login'), [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin, 'platform');
    $response->assertRedirect(route('platform.offices.index'));
});

test('a platform admin cannot log in with an incorrect password', function () {
    $admin = PlatformAdmin::factory()->create();

    $this->post(route('platform.login'), [
        'email' => $admin->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest('platform');
});

test('unauthenticated requests to platform routes redirect to the platform login screen, not the tenant one', function () {
    $response = $this->get(route('platform.offices.index'));

    $response->assertRedirect(route('platform.login'));
});

test('a tenant (web guard) user cannot access platform admin routes', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->owner()->create();

    $response = $this->actingAs($user)->get(route('platform.offices.index'));

    $response->assertRedirect(route('platform.login'));
});

test('a platform admin cannot access tenant routes', function () {
    $admin = PlatformAdmin::factory()->create();

    $response = $this->actingAs($admin, 'platform')->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});
