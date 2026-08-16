<?php

use App\Models\Client;
use App\Models\CustomFieldDefinition;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;

it('saves a custom field value when creating a task', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $definition = CustomFieldDefinition::factory()->for($office)->forProcedureTask()->create(['field_type' => 'text']);

    $response = $this->actingAs($user)->post(route('tasks.store'), [
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'title' => $procedureType->name,
        'due_date' => '2026-10-10',
        'custom_fields' => [$definition->id => 'カスタム値'],
    ]);

    $response->assertRedirect(route('tasks.index'));
    expect(ProcedureTask::sole()->custom_fields[$definition->id])->toBe('カスタム値');
});

it('rejects an invalid custom field value when updating a task', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->owner()->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $definition = CustomFieldDefinition::factory()->for($office)->forProcedureTask()->create(['field_type' => 'number']);
    $task = ProcedureTask::factory()->for($client)->for($procedureType)->create(['office_id' => $office->id]);

    $response = $this->actingAs($user)->put(route('tasks.update', $task->id), [
        'status' => $task->status->value,
        'custom_fields' => [$definition->id => 'not-a-number'],
    ]);

    $response->assertSessionHasErrors("custom_fields.{$definition->id}");
});

it('only exposes procedure_task-target field definitions, not client-target ones, on the task create form', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    CustomFieldDefinition::factory()->for($office)->create(['label' => '顧問先用']);
    CustomFieldDefinition::factory()->for($office)->forProcedureTask()->create(['label' => 'タスク用']);

    $response = $this->actingAs($user)->get(route('tasks.create'));

    $response->assertInertia(fn ($page) => $page
        ->has('customFieldDefinitions', 1)
        ->where('customFieldDefinitions.0.label', 'タスク用')
    );
});
