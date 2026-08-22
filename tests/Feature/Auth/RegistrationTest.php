<?php

use App\Enums\UserRole;
use App\Models\Office;
use App\Models\PlatformAdmin;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'office_name' => 'テスト社労士事務所',
        'office_code' => 'test-sharoushi-office',
        'name' => 'Test User',
        'login_id' => 'test-user',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = auth()->user();
    expect($user->role)->toBe(UserRole::Owner);
    expect($user->office->name)->toBe('テスト社労士事務所');
});

test('new users receive a verification email and cannot reach the dashboard until verified', function () {
    Notification::fake();

    $this->post('/register', [
        'office_name' => 'メール確認テスト事務所',
        'office_code' => 'verify-email-office',
        'name' => 'Verify User',
        'login_id' => 'verify-user',
        'email' => 'verify-user@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = auth()->user();
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);

    $response = $this->get(route('dashboard', absolute: false));
    $response->assertRedirect(route('verification.notice', absolute: false));
});

test('office_code must be unique when registering', function () {
    $existingOffice = Office::factory()->create(['office_code' => 'taken-office-code']);

    $response = $this->post('/register', [
        'office_name' => '重複コードテスト事務所',
        'office_code' => 'taken-office-code',
        'name' => 'Test User 3',
        'login_id' => 'test-user-3',
        'email' => 'test3@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('office_code');
    expect(Office::where('name', '重複コードテスト事務所')->exists())->toBeFalse();
});

test('registering while a platform admin session is active in the same browser still assigns the new office correctly', function () {
    $admin = PlatformAdmin::factory()->create();
    $otherOffice = Office::factory()->create();

    $response = $this->actingAs($admin, 'platform')->post('/register', [
        'office_name' => 'もう一つのテスト事務所',
        'office_code' => 'another-test-office',
        'name' => 'Test User 2',
        'login_id' => 'test-user-2',
        'email' => 'test2@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = auth()->user();
    $office = Office::where('name', 'もう一つのテスト事務所')->sole();
    expect($user->office_id)->toBe($office->id)
        ->and($user->office_id)->not->toBe($otherOffice->id);
});
