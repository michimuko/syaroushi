<?php

use App\Models\Office;
use App\Models\User;

test('users belonging to an inactive office cannot log in', function () {
    $office = Office::factory()->inactive()->create();
    $user = User::factory()->for($office)->create();

    $response = $this->post('/login', [
        'login_id' => $user->login_id,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('login_id');
    $this->assertGuest();
});

test('users can log in again once their office is reactivated', function () {
    $office = Office::factory()->inactive()->create();
    $user = User::factory()->for($office)->create();

    $this->post('/login', [
        'login_id' => $user->login_id,
        'password' => 'password',
    ]);
    $this->assertGuest();

    $office->update(['is_active' => true]);

    $response = $this->post('/login', [
        'login_id' => $user->login_id,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users belonging to an active office can log in as usual', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $response = $this->post('/login', [
        'login_id' => $user->login_id,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
