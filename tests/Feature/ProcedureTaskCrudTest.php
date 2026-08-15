<?php

use App\Models\Client;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('index screen lists only the current office\'s tasks', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'title' => '自事務所のタスク',
    ]);

    $otherClient = Client::factory()->for($otherOffice)->create();
    ProcedureTask::factory()->for($otherOffice)->create([
        'client_id' => $otherClient->id,
        'procedure_type_id' => $procedureType->id,
        'title' => '他事務所のタスク',
    ]);

    $response = $this->actingAs($user)->get(route('tasks.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('ProcedureTasks/Index')
        ->has('tasks.data', 1)
        ->where('tasks.data.0.title', '自事務所のタスク')
    );
});

test('index screen filters by status, client, assigned user, and due date range', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $otherClient = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    $target = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'status' => 'in_progress',
        'assigned_user_id' => $staff->id,
        'due_date' => '2026-09-15',
    ]);

    ProcedureTask::factory()->for($office)->create([
        'client_id' => $otherClient->id,
        'procedure_type_id' => $procedureType->id,
        'status' => 'completed',
        'due_date' => '2026-01-01',
    ]);

    $response = $this->actingAs($user)->get(route('tasks.index', [
        'status' => 'in_progress',
        'client_id' => $client->id,
        'assigned_user_id' => $staff->id,
        'due_from' => '2026-09-01',
        'due_to' => '2026-09-30',
    ]));

    $response->assertInertia(fn ($page) => $page
        ->has('tasks.data', 1)
        ->where('tasks.data.0.id', $target->id)
    );
});

test('is_overdue is true only for non-completed tasks past their due date', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    $overdue = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'due_date' => now()->subDays(5),
        'status' => 'in_progress',
    ]);

    $overdueButCompleted = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'due_date' => now()->subDays(3),
        'status' => 'completed',
    ]);

    expect($overdue->is_overdue)->toBeTrue()
        ->and($overdueButCompleted->is_overdue)->toBeFalse();

    $response = $this->actingAs($user)->get(route('tasks.index'));

    $response->assertInertia(fn ($page) => $page
        ->where('tasks.data.0.is_overdue', true)
        ->where('tasks.data.1.is_overdue', false)
    );
});

test('index screen requires authentication', function () {
    $response = $this->get(route('tasks.index'));

    $response->assertRedirect(route('login'));
});

test('create screen can be rendered with client/staff/procedure type options', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    Client::factory()->for($office)->create();
    ProcedureType::factory()->create(['is_active' => true]);
    ProcedureType::factory()->create(['is_active' => false]);

    $response = $this->actingAs($user)->get(route('tasks.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('ProcedureTasks/Create')
        ->has('clientOptions', 1)
        ->has('procedureTypeOptions', 1)
    );
});

test('a task can be created with valid data and defaults to not_started', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    $response = $this->actingAs($user)->post(route('tasks.store'), [
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'title' => $procedureType->name,
        'due_date' => '2026-10-10',
        'assigned_user_id' => '',
        'notes' => '',
    ]);

    $response->assertRedirect(route('tasks.index'));

    $task = ProcedureTask::sole();
    expect($task->status->value)->toBe('not_started')
        ->and($task->office_id)->toBe($office->id)
        ->and($task->due_date->toDateString())->toBe('2026-10-10');
});

test('a task cannot be created for a client belonging to another office', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $foreignClient = Client::factory()->for($otherOffice)->create();
    $procedureType = ProcedureType::factory()->create();

    $response = $this->actingAs($user)->post(route('tasks.store'), [
        'client_id' => $foreignClient->id,
        'procedure_type_id' => $procedureType->id,
        'title' => 'テスト',
        'due_date' => '2026-10-10',
    ]);

    $response->assertSessionHasErrors('client_id');
    expect(ProcedureTask::count())->toBe(0);
});

test('task creation requires client, procedure type, title, and due date', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $response = $this->actingAs($user)->post(route('tasks.store'), []);

    $response->assertSessionHasErrors(['client_id', 'procedure_type_id', 'title', 'due_date']);
});

test('index query does not N+1 when loading relations', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    ProcedureTask::factory()->for($office)->count(5)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
    ]);

    DB::enableQueryLog();
    $this->actingAs($user)->get(route('tasks.index'));
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queryCount)->toBeLessThan(12);
});
