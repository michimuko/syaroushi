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
        'login_id' => $admin->login_id,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin, 'platform');
    $response->assertRedirect(route('platform.offices.index'));
});

test('a platform admin cannot log in with an email address instead of the login_id', function () {
    $admin = PlatformAdmin::factory()->create();

    $response = $this->post(route('platform.login'), [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('login_id');
    $this->assertGuest('platform');
});

test('a platform admin cannot log in with an incorrect password', function () {
    $admin = PlatformAdmin::factory()->create();

    $this->post(route('platform.login'), [
        'login_id' => $admin->login_id,
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

test('platform login ignores a stray tenant url left over in the session and goes to the offices index', function () {
    $admin = PlatformAdmin::factory()->create();

    // 未ログイン状態で社労士側の保護ページにアクセスすると、url.intendedに管理画面外のURLが記録される
    $this->get(route('dashboard'));

    $response = $this->post(route('platform.login'), [
        'login_id' => $admin->login_id,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin, 'platform');
    $response->assertRedirect(route('platform.offices.index'));
});

test('logging out of the platform guard preserves an active web guard session', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->owner()->create();
    $admin = PlatformAdmin::factory()->create();

    $this->post('/login', [
        'office_code' => $office->office_code,
        'login_id' => $user->login_id,
        'password' => 'password',
    ]);

    $this->post(route('platform.login'), [
        'login_id' => $admin->login_id,
        'password' => 'password',
    ]);

    $webSessionKey = collect($this->app['session']->all())
        ->keys()
        ->first(fn ($key) => str_starts_with($key, 'login_web_'));

    expect($webSessionKey)->not->toBeNull();

    $this->post(route('platform.logout'));

    $this->assertGuest('platform');
    expect($this->app['session']->get($webSessionKey))->toBe($user->getAuthIdentifier());
});
