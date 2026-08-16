<?php

use App\Models\Office;
use App\Models\User;

it('requires authentication', function () {
    $this->get(route('settings.desktop-app.index'))->assertRedirect(route('login'));
});

it('issues a token with the desktop-notifications ability and flashes the plaintext once', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $response = $this->actingAs($user)->post(route('settings.desktop-app.token.store'));

    $response->assertRedirect(route('settings.desktop-app.index'));
    $response->assertSessionHas('desktopAppToken');

    $token = $user->tokens()->sole();
    expect($token->name)->toBe('desktop-app')
        ->and($token->abilities)->toBe(['desktop-notifications:read']);
});

it('replaces the previous token when reissuing', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $this->actingAs($user)->post(route('settings.desktop-app.token.store'));
    $firstTokenId = $user->tokens()->sole()->id;

    $this->actingAs($user)->post(route('settings.desktop-app.token.store'));

    $token = $user->tokens()->sole();
    expect($token->id)->not->toBe($firstTokenId);
});

it('revokes the token', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $user->createToken('desktop-app', ['desktop-notifications:read']);

    $response = $this->actingAs($user)->delete(route('settings.desktop-app.token.destroy'));

    $response->assertRedirect(route('settings.desktop-app.index'));
    expect($user->tokens()->count())->toBe(0);
});

it('does not let a user revoke or see another user\'s token via the shared token name', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->create();
    $owner->createToken('desktop-app', ['desktop-notifications:read']);

    $otherOffice = Office::factory()->create();
    $otherUser = User::factory()->for($otherOffice)->create();

    $this->actingAs($otherUser)->delete(route('settings.desktop-app.token.destroy'));

    expect($owner->tokens()->count())->toBe(1);
});
