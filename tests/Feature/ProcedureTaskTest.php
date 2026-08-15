<?php

use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;

it('creates a task with default status and casts', function () {
    $office = Office::factory()->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'title' => '算定基礎届の提出',
    ]);

    expect($task->status)->toBe(TaskStatus::NotStarted)
        ->and($task->client->id)->toBe($client->id)
        ->and($task->procedureType->id)->toBe($procedureType->id)
        ->and($task->completed_at)->toBeNull();
});

it('casts calc_result and custom_fields to arrays', function () {
    $office = Office::factory()->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'calc_result' => ['overtime_hours' => 42],
        'custom_fields' => ['管理番号' => 'T-001'],
    ]);

    $task->refresh();
    expect($task->calc_result)->toBe(['overtime_hours' => 42])
        ->and($task->custom_fields)->toBe(['管理番号' => 'T-001']);
});

it('scopes tasks to the authenticated user\'s office only', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();
    $userA = User::factory()->for($officeA)->create();

    $clientA = Client::factory()->for($officeA)->create();
    $clientB = Client::factory()->for($officeB)->create();
    $procedureType = ProcedureType::factory()->create();

    ProcedureTask::factory()->for($officeA)->create([
        'client_id' => $clientA->id,
        'procedure_type_id' => $procedureType->id,
    ]);
    ProcedureTask::factory()->for($officeB)->create([
        'client_id' => $clientB->id,
        'procedure_type_id' => $procedureType->id,
    ]);

    $this->actingAs($userA);

    expect(ProcedureTask::count())->toBe(1);
});

it('always assigns office_id from the authenticated user on create, ignoring a spoofed value', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();
    $userA = User::factory()->for($officeA)->create();
    $clientA = Client::factory()->for($officeA)->create();
    $procedureType = ProcedureType::factory()->create();

    $this->actingAs($userA);

    $task = ProcedureTask::create([
        'office_id' => $officeB->id, // 悪意ある入力を想定
        'client_id' => $clientA->id,
        'procedure_type_id' => $procedureType->id,
        'title' => 'なりすまし疑いタスク',
        'due_date' => now()->addDays(30),
        'status' => TaskStatus::NotStarted,
    ]);

    expect($task->office_id)->toBe($officeA->id);
});
