<?php

use App\Models\Office;
use App\Models\PlatformAdmin;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('login screen prefills the office_code from a previously remembered cookie', function () {
    $office = Office::factory()->create();

    $response = $this->withCookie('remembered_office_code', $office->office_code)->get('/login');

    $response->assertInertia(fn ($page) => $page
        ->component('Auth/Login')
        ->where('rememberedOfficeCode', $office->office_code)
    );
});

test('login screen has no remembered office_code on first visit', function () {
    $response = $this->get('/login');

    $response->assertInertia(fn ($page) => $page
        ->component('Auth/Login')
        ->where('rememberedOfficeCode', null)
    );
});

test('a successful login remembers the office_code in a long-lived cookie', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'office_code' => $user->office->office_code,
        'login_id' => $user->login_id,
        'password' => 'password',
    ]);

    $response->assertCookie('remembered_office_code', $user->office->office_code);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'office_code' => $user->office->office_code,
        'login_id' => $user->login_id,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('logging in with an unknown office_code fails with the same generic error as wrong credentials', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'office_code' => 'no-such-office',
        'login_id' => $user->login_id,
        'password' => 'password',
    ]);

    // 事業所IDの存在有無を推測されないよう、認証情報が誤っている場合と同じ
    // フィールド(login_id)・同じ文言のエラーになることを確認する。
    $response->assertSessionHasErrors(['login_id' => trans('auth.failed')]);
    $response->assertSessionDoesntHaveErrors('office_code');
    $this->assertGuest();
});

test('logging in with an email address instead of the login_id fails', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'office_code' => $user->office->office_code,
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('login_id');
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'office_code' => $user->office->office_code,
        'login_id' => $user->login_id,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('the same login_id can belong to different offices without colliding', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();
    User::factory()->for($officeA)->create(['login_id' => 'taro', 'password' => bcrypt('password-a')]);
    $userB = User::factory()->for($officeB)->create(['login_id' => 'taro', 'password' => bcrypt('password-b')]);

    $response = $this->post('/login', [
        'office_code' => $officeB->office_code,
        'login_id' => 'taro',
        'password' => 'password-b',
    ]);

    $this->assertAuthenticatedAs($userB);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect(route('login', absolute: false));
});

test('logging in ignores a stray platform admin url left over in the session and goes to the dashboard', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->owner()->create();

    // 未ログイン状態で運営管理画面の保護ページにアクセスすると、url.intendedにadmin配下のURLが記録される
    $this->get(route('platform.offices.index'));

    $response = $this->post('/login', [
        'office_code' => $office->office_code,
        'login_id' => $user->login_id,
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
        'office_code' => $office->office_code,
        'login_id' => $user->login_id,
        'password' => 'password',
    ]);

    $this->post(route('platform.login'), [
        'login_id' => $admin->login_id,
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
