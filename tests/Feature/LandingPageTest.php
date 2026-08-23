<?php

use App\Models\User;

test('guests see the landing page at the root url', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertViewIs('marketing.landing');
    $response->assertSee('無料トライアルを始める');
});

test('the root url shows the landing page even for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(200);
    $response->assertViewIs('marketing.landing');
});

test('the manual PDF can be downloaded from the landing page without authentication', function () {
    $response = $this->get('/manual.pdf');

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});
