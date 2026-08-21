<?php

use App\Enums\UserRole;
use App\Models\Office;
use App\Models\PlatformAdmin;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'office_name' => 'テスト社労士事務所',
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

test('registering while a platform admin session is active in the same browser still assigns the new office correctly', function () {
    $admin = PlatformAdmin::factory()->create();
    $otherOffice = Office::factory()->create();

    $response = $this->actingAs($admin, 'platform')->post('/register', [
        'office_name' => 'もう一つのテスト事務所',
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
