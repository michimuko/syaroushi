<?php

use App\Models\Client;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;

test('dashboard requires authentication', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('dashboard summarizes overdue, due-soon, and due-later counts for the current office only', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $otherClient = Client::factory()->for($otherOffice)->create();
    $procedureType = ProcedureType::factory()->create();

    // 期限超過（未完了）
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'due_date' => now()->subDay(),
        'status' => 'in_progress',
    ]);

    // 期限超過だが完了済み → カウントしない
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'due_date' => now()->subDay(),
        'status' => 'completed',
    ]);

    // 7日以内
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'due_date' => now()->addDays(3),
        'status' => 'not_started',
    ]);

    // 30日以内（8〜29日）
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'due_date' => now()->addDays(20),
        'status' => 'not_started',
    ]);

    // 30日より先 → どのバケットにも入らない
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'due_date' => now()->addDays(60),
        'status' => 'not_started',
    ]);

    // 他事務所のタスク → カウントしない
    ProcedureTask::factory()->for($otherOffice)->create([
        'client_id' => $otherClient->id,
        'procedure_type_id' => $procedureType->id,
        'due_date' => now()->subDay(),
        'status' => 'in_progress',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('summary.overdue', 1)
        ->where('summary.dueSoon', 1)
        ->where('summary.dueLater', 1)
    );
});

test('dashboard lists upcoming non-completed tasks ordered by due date', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    $later = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'due_date' => now()->addDays(10),
        'status' => 'not_started',
        'title' => '後の手続き',
    ]);

    $sooner = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'due_date' => now()->addDays(2),
        'status' => 'in_progress',
        'title' => '直近の手続き',
    ]);

    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'due_date' => now()->addDay(),
        'status' => 'completed',
        'title' => '完了済みの手続き',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->has('upcomingTasks', 2)
        ->where('upcomingTasks.0.id', $sooner->id)
        ->where('upcomingTasks.1.id', $later->id)
    );
});
