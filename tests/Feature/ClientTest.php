<?php

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\Office;
use App\Models\User;

it('creates a client belonging to an office with defaults', function () {
    $client = Client::factory()->create(['name' => 'テスト商事株式会社']);

    expect($client->name)->toBe('テスト商事株式会社')
        ->and($client->status)->toBe(ClientStatus::Active)
        ->and($client->office)->toBeInstanceOf(Office::class)
        ->and($client->assigned_user_id)->toBeNull()
        ->and($client->custom_fields)->toBeNull();
});

it('casts custom_fields to an array', function () {
    $client = Client::factory()->create([
        'custom_fields' => ['管理番号' => 'A-001', '契約プラン' => 'standard'],
    ]);

    $client->refresh();

    expect($client->custom_fields)->toBe(['管理番号' => 'A-001', '契約プラン' => 'standard']);
});

it('associates an assigned user within the same office', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create(['assigned_user_id' => $user->id]);

    expect($client->assignedUser->id)->toBe($user->id);
});

it('scopes clients to the authenticated user\'s office only', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();

    $userA = User::factory()->for($officeA)->create();
    Client::factory()->for($officeA)->create();
    Client::factory()->for($officeB)->create();

    $this->actingAs($userA);

    expect(Client::all())->toHaveCount(1);
});

it('always assigns office_id from the authenticated user on create', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();
    $userA = User::factory()->for($officeA)->create();

    $this->actingAs($userA);

    $client = Client::create([
        'office_id' => $officeB->id, // 悪意ある入力を想定
        'name' => 'なりすまし疑い顧問先',
    ]);

    expect($client->office_id)->toBe($officeA->id);
});
