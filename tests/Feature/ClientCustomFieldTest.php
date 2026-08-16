<?php

use App\Models\Client;
use App\Models\CustomFieldDefinition;
use App\Models\Office;
use App\Models\User;

it('saves values for text, number, date, select and checkbox custom fields on create', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $text = CustomFieldDefinition::factory()->for($office)->create(['field_type' => 'text']);
    $number = CustomFieldDefinition::factory()->for($office)->create(['field_type' => 'number']);
    $date = CustomFieldDefinition::factory()->for($office)->create(['field_type' => 'date']);
    $select = CustomFieldDefinition::factory()->for($office)->select()->create();
    $checkbox = CustomFieldDefinition::factory()->for($office)->create(['field_type' => 'checkbox']);

    $response = $this->actingAs($user)->post(route('clients.store'), [
        'name' => '新規株式会社',
        'status' => 'active',
        'custom_fields' => [
            $text->id => 'A-001',
            $number->id => '42.5',
            $date->id => '2026-04-01',
            $select->id => 'A',
            $checkbox->id => true,
        ],
    ]);

    $response->assertRedirect(route('clients.index'));

    $client = Client::sole();
    expect($client->custom_fields[$text->id])->toBe('A-001')
        ->and($client->custom_fields[$number->id])->toBe('42.5')
        ->and($client->custom_fields[$date->id])->toBe('2026-04-01')
        ->and($client->custom_fields[$select->id])->toBe('A')
        ->and($client->custom_fields[$checkbox->id])->toBeTrue();
});

it('rejects a number field with a non-numeric value', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $number = CustomFieldDefinition::factory()->for($office)->create(['field_type' => 'number']);

    $response = $this->actingAs($user)->post(route('clients.store'), [
        'name' => '新規株式会社',
        'status' => 'active',
        'custom_fields' => [$number->id => 'not-a-number'],
    ]);

    $response->assertSessionHasErrors("custom_fields.{$number->id}");
    expect(Client::count())->toBe(0);
});

it('rejects a select field value outside the defined options', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $select = CustomFieldDefinition::factory()->for($office)->select()->create();

    $response = $this->actingAs($user)->post(route('clients.store'), [
        'name' => '新規株式会社',
        'status' => 'active',
        'custom_fields' => [$select->id => 'Z'],
    ]);

    $response->assertSessionHasErrors("custom_fields.{$select->id}");
});

it('updates an existing custom field value and leaves other fields unaffected', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $text = CustomFieldDefinition::factory()->for($office)->create(['field_type' => 'text']);
    $client = Client::factory()->for($office)->create(['custom_fields' => [$text->id => '旧値']]);

    $response = $this->actingAs($user)->put(route('clients.update', $client->id), [
        'name' => $client->name,
        'status' => 'active',
        'custom_fields' => [$text->id => '新値'],
    ]);

    $response->assertRedirect(route('clients.index'));
    expect($client->refresh()->custom_fields[$text->id])->toBe('新値');
});

it('does not fail when saving a client after its custom field definition was deleted', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $text = CustomFieldDefinition::factory()->for($office)->create(['field_type' => 'text']);
    $client = Client::factory()->for($office)->create(['custom_fields' => [$text->id => '削除前の値']]);
    $text->delete();

    $response = $this->actingAs($user)->put(route('clients.update', $client->id), [
        'name' => $client->name,
        'status' => 'active',
    ]);

    $response->assertRedirect(route('clients.index'));
});

it('only exposes field definitions belonging to the authenticated user\'s office when rendering the create form', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();
    $userA = User::factory()->for($officeA)->create();
    CustomFieldDefinition::factory()->for($officeA)->create(['label' => '自事務所の項目']);
    CustomFieldDefinition::factory()->for($officeB)->create(['label' => '他事務所の項目']);

    $response = $this->actingAs($userA)->get(route('clients.create'));

    $response->assertInertia(fn ($page) => $page
        ->has('customFieldDefinitions', 1)
        ->where('customFieldDefinitions.0.label', '自事務所の項目')
    );
});
