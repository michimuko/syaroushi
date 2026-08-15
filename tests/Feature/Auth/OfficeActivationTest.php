<?php

use App\Models\Office;
use App\Models\User;

test('users belonging to an inactive office cannot log in', function () {
    $office = Office::factory()->inactive()->create();
    $user = User::factory()->for($office)->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('users can log in again once their office is reactivated', function () {
    $office = Office::factory()->inactive()->create();
    $user = User::factory()->for($office)->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    $this->assertGuest();

    $office->update(['is_active' => true]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users belonging to an active office can log in as usual', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
