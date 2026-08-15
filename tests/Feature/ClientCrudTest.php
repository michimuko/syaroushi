<?php

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\Office;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('index screen can be rendered and lists only the current office\'s clients', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    Client::factory()->for($office)->create(['name' => '自事務所の顧問先']);
    Client::factory()->for($otherOffice)->create(['name' => '他事務所の顧問先']);

    $response = $this->actingAs($user)->get(route('clients.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Clients/Index')
        ->has('clients.data', 1)
        ->where('clients.data.0.name', '自事務所の顧問先')
    );
});

test('index screen filters by search keyword', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    Client::factory()->for($office)->create(['name' => 'アルファ商事']);
    Client::factory()->for($office)->create(['name' => 'ベータ工業']);

    $response = $this->actingAs($user)->get(route('clients.index', ['search' => 'アルファ']));

    $response->assertInertia(fn ($page) => $page
        ->has('clients.data', 1)
        ->where('clients.data.0.name', 'アルファ商事')
    );
});

test('index screen filters by status', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    Client::factory()->for($office)->create(['status' => ClientStatus::Active]);
    Client::factory()->for($office)->inactive()->create();

    $response = $this->actingAs($user)->get(route('clients.index', ['status' => 'inactive']));

    $response->assertInertia(fn ($page) => $page->has('clients.data', 1));
});

test('index screen requires authentication', function () {
    $response = $this->get(route('clients.index'));

    $response->assertRedirect(route('login'));
});

test('index query does not N+1 when loading assigned users', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $staff = User::factory()->for($office)->create();

    Client::factory()->for($office)->count(5)->create(['assigned_user_id' => $staff->id]);

    DB::enableQueryLog();
    $this->actingAs($user)->get(route('clients.index'));
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queryCount)->toBeLessThan(10);
});
