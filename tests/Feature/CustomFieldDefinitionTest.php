<?php

use App\Models\CustomFieldDefinition;
use App\Models\Office;
use App\Models\User;

it('allows an owner to create a text field definition', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();

    $response = $this->actingAs($owner)->post(route('settings.custom-fields.store'), [
        'target' => 'client',
        'label' => '管理番号',
        'field_type' => 'text',
    ]);

    $response->assertRedirect();
    $definition = CustomFieldDefinition::sole();
    expect($definition->label)->toBe('管理番号')
        ->and($definition->target->value)->toBe('client')
        ->and($definition->field_type->value)->toBe('text')
        ->and($definition->options)->toBeNull()
        ->and($definition->office_id)->toBe($office->id);
});

it('requires options when creating a select field definition', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();

    $this->actingAs($owner)->post(route('settings.custom-fields.store'), [
        'target' => 'client',
        'label' => '業界',
        'field_type' => 'select',
    ])->assertSessionHasErrors('options');

    $response = $this->actingAs($owner)->post(route('settings.custom-fields.store'), [
        'target' => 'client',
        'label' => '業界',
        'field_type' => 'select',
        'options' => ['製造業', '小売業'],
    ]);

    $response->assertRedirect();
    expect(CustomFieldDefinition::sole()->options)->toBe(['製造業', '小売業']);
});

it('denies a staff member from creating a field definition', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();

    $this->actingAs($staff)->post(route('settings.custom-fields.store'), [
        'target' => 'client',
        'label' => '管理番号',
        'field_type' => 'text',
    ])->assertForbidden();
});

it('allows an owner to rename a field and change its select options, but not its type', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $definition = CustomFieldDefinition::factory()->for($office)->select()->create(['label' => '旧ラベル']);

    $response = $this->actingAs($owner)->put(route('settings.custom-fields.update', $definition->id), [
        'label' => '新ラベル',
        'options' => ['X', 'Y', 'Z'],
    ]);

    $response->assertRedirect();
    $definition->refresh();
    expect($definition->label)->toBe('新ラベル')
        ->and($definition->options)->toBe(['X', 'Y', 'Z'])
        ->and($definition->field_type->value)->toBe('select');
});

it('denies a staff member from updating or deleting a field definition', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $definition = CustomFieldDefinition::factory()->for($office)->create();

    $this->actingAs($staff)->put(route('settings.custom-fields.update', $definition->id), [
        'label' => '変更後',
    ])->assertForbidden();

    $this->actingAs($staff)->delete(route('settings.custom-fields.destroy', $definition->id))
        ->assertForbidden();
});

it('allows an owner to delete a field definition', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $definition = CustomFieldDefinition::factory()->for($office)->create();

    $this->actingAs($owner)->delete(route('settings.custom-fields.destroy', $definition->id))
        ->assertRedirect();

    expect(CustomFieldDefinition::find($definition->id))->toBeNull();
});

it('prevents an owner from updating or deleting another office\'s field definition', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();
    $ownerA = User::factory()->for($officeA)->owner()->create();
    $definitionB = CustomFieldDefinition::factory()->for($officeB)->create();

    $this->actingAs($ownerA)->put(route('settings.custom-fields.update', $definitionB->id), [
        'label' => '乗っ取り試行',
    ])->assertNotFound();

    $this->actingAs($ownerA)->delete(route('settings.custom-fields.destroy', $definitionB->id))
        ->assertNotFound();
});

it('scopes the index listing to the authenticated user\'s office only', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();
    $ownerA = User::factory()->for($officeA)->owner()->create();
    CustomFieldDefinition::factory()->for($officeA)->create(['label' => '自事務所']);
    CustomFieldDefinition::factory()->for($officeB)->create(['label' => '他事務所']);

    $response = $this->actingAs($ownerA)->get(route('settings.custom-fields.index'));

    $response->assertInertia(fn ($page) => $page
        ->has('definitions', 1)
        ->where('definitions.0.label', '自事務所')
    );
});
