<?php

use App\Models\Office;
use App\Models\PlatformAdmin;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('logging in ignores a stray platform admin url left over in the session and goes to the dashboard', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->owner()->create();

    // 未ログイン状態で運営管理画面の保護ページにアクセスすると、url.intendedにadmin配下のURLが記録される
    $this->get(route('platform.offices.index'));

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('logging out of the web guard preserves an active platform guard session', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->owner()->create();
    $admin = PlatformAdmin::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->post(route('platform.login'), [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $platformSessionKey = collect($this->app['session']->all())
        ->keys()
        ->first(fn ($key) => str_starts_with($key, 'login_platform_'));

    expect($platformSessionKey)->not->toBeNull();

    $this->post('/logout');

    $this->assertGuest('web');
    expect($this->app['session']->get($platformSessionKey))->toBe($admin->getAuthIdentifier());
});
