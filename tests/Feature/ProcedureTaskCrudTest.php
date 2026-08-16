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

test('index screen exposes can_update per task based on assignment', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $staff = User::factory()->for($office)->create();
    $otherStaff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    $ownTask = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
        'due_date' => '2026-07-01',
    ]);
    $othersTask = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $otherStaff->id,
        'due_date' => '2026-07-02',
    ]);
    // false判定の行より後ろにも真の行を置き、Collection::each()がfalseで打ち切られないことを確認する
    $ownSecondTask = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
        'due_date' => '2026-07-03',
    ]);

    $this->actingAs($owner)->get(route('tasks.index'))->assertInertia(fn ($page) => $page
        ->where('tasks.data.0.can_update', true)
        ->where('tasks.data.1.can_update', true)
        ->where('tasks.data.2.can_update', true)
    );

    $this->actingAs($staff)->get(route('tasks.index'))->assertInertia(fn ($page) => $page
        ->where('tasks.data.0.id', $ownTask->id)
        ->where('tasks.data.0.can_update', true)
        ->where('tasks.data.1.id', $othersTask->id)
        ->where('tasks.data.1.can_update', false)
        ->where('tasks.data.2.id', $ownSecondTask->id)
        ->where('tasks.data.2.can_update', true)
    );
});

test('inline status update redirects back to the given tasks list URL to preserve filters', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
        'status' => 'not_started',
    ]);

    $filteredUrl = '/tasks?status=in_progress&client_id='.$client->id;

    $response = $this->actingAs($staff)->put(route('tasks.update', $task), [
        'status' => 'in_progress',
        'return_to' => $filteredUrl,
    ]);

    $response->assertRedirect($filteredUrl);
});

test('inline status update ignores a return_to pointing outside the tasks list', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
        'status' => 'not_started',
    ]);

    $response = $this->actingAs($staff)->put(route('tasks.update', $task), [
        'status' => 'in_progress',
        'return_to' => 'https://evil.example.com/phishing',
    ]);

    $response->assertRedirect(route('tasks.index'));
});

test('inline status update rejects a protocol-relative return_to', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
        'status' => 'not_started',
    ]);

    $response = $this->actingAs($staff)->put(route('tasks.update', $task), [
        'status' => 'in_progress',
        'return_to' => '//evil.example.com/phishing',
    ]);

    $response->assertRedirect(route('tasks.index'));
});

test('editing from the calendar returns to the calendar after saving', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
        'status' => 'not_started',
    ]);

    $editResponse = $this->actingAs($staff)->get(route('tasks.edit', $task).'?return_to='.urlencode('/calendar'));
    $editResponse->assertInertia(fn ($page) => $page->where('returnTo', '/calendar'));

    $updateResponse = $this->actingAs($staff)->put(route('tasks.update', $task), [
        'status' => 'in_progress',
        'return_to' => '/calendar',
    ]);

    $updateResponse->assertRedirect('/calendar');
});

test('updating only status via the inline editor does not clear assigned_user_id or notes', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
        'notes' => '既存のメモ',
        'status' => 'not_started',
    ]);

    $response = $this->actingAs($staff)->put(route('tasks.update', $task), [
        'status' => 'in_progress',
    ]);

    $response->assertRedirect(route('tasks.index'));

    $task->refresh();
    expect($task->status->value)->toBe('in_progress')
        ->and($task->assigned_user_id)->toBe($staff->id)
        ->and($task->notes)->toBe('既存のメモ');
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

test('an owner can view and update any task in their office', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'status' => 'not_started',
    ]);

    $this->actingAs($owner)->get(route('tasks.edit', $task))->assertOk();

    $response = $this->actingAs($owner)->put(route('tasks.update', $task), [
        'status' => 'in_progress',
        'assigned_user_id' => $staff->id,
        'notes' => 'ownerによる更新',
    ]);

    $response->assertRedirect(route('tasks.index'));

    $task->refresh();
    expect($task->status->value)->toBe('in_progress')
        ->and($task->assigned_user_id)->toBe($staff->id)
        ->and($task->notes)->toBe('ownerによる更新');
});

test('a staff member can update only their own assigned task', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $otherStaff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    $ownTask = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
    ]);
    $othersTask = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $otherStaff->id,
    ]);

    $this->actingAs($staff)->put(route('tasks.update', $ownTask), [
        'status' => 'in_progress',
        'assigned_user_id' => $staff->id,
        'notes' => '担当分の更新',
    ])->assertRedirect(route('tasks.index'));
    expect($ownTask->fresh()->status->value)->toBe('in_progress');

    // 閲覧はできるが編集はできない（403）
    $this->actingAs($staff)->get(route('tasks.edit', $othersTask))->assertOk();
    $this->actingAs($staff)->put(route('tasks.update', $othersTask), [
        'status' => 'in_progress',
        'notes' => 'なりすまし更新試行',
    ])->assertForbidden();
    expect($othersTask->fresh()->status->value)->toBe('not_started');
});

test('an assigned staff member can correct a task\'s due date while original_due_date stays unchanged', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
        'due_date' => '2026-07-10',
        'original_due_date' => '2026-07-10',
    ]);

    $response = $this->actingAs($staff)->put(route('tasks.update', $task), [
        'status' => 'in_progress',
        'due_date' => '2026-07-17',
        'assigned_user_id' => $staff->id,
        'notes' => '雇用日の訂正に伴い期限を修正',
    ]);

    $response->assertRedirect(route('tasks.index'));

    $task->refresh();
    expect($task->due_date->toDateString())->toBe('2026-07-17')
        ->and($task->original_due_date->toDateString())->toBe('2026-07-10');
});

test('due date is required and must be a valid date when present', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
    ]);

    $response = $this->actingAs($owner)->put(route('tasks.update', $task), [
        'status' => 'in_progress',
        'due_date' => 'not-a-date',
    ]);

    $response->assertSessionHasErrors('due_date');
});

test('a staff member cannot correct the due date of a task assigned to someone else', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $otherStaff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $othersTask = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $otherStaff->id,
        'due_date' => '2026-07-10',
    ]);

    $this->actingAs($staff)->put(route('tasks.update', $othersTask), [
        'status' => 'in_progress',
        'due_date' => '2026-08-01',
    ])->assertForbidden();

    expect($othersTask->fresh()->due_date->toDateString())->toBe('2026-07-10');
});

test('completed_at is set when status becomes completed and cleared when reverted', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'status' => 'submitted',
    ]);

    $this->actingAs($owner)->put(route('tasks.update', $task), [
        'status' => 'completed',
    ]);
    expect($task->fresh()->completed_at)->not->toBeNull();

    $this->actingAs($owner)->put(route('tasks.update', $task), [
        'status' => 'in_progress',
    ]);
    expect($task->fresh()->completed_at)->toBeNull();
});

test('a task in another office returns 404 on edit and update', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($otherOffice)->create();
    $procedureType = ProcedureType::factory()->create();
    $foreignTask = ProcedureTask::factory()->for($otherOffice)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
    ]);

    $this->actingAs($user)->get(route('tasks.edit', $foreignTask))->assertNotFound();
    $this->actingAs($user)->put(route('tasks.update', $foreignTask), [
        'status' => 'completed',
    ])->assertNotFound();
});

test('index filters by procedure_type_id', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $matchingType = ProcedureType::factory()->create(['name' => '算定基礎届']);
    $otherType = ProcedureType::factory()->create(['name' => '労働保険年度更新']);

    $matchingTask = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $matchingType->id,
    ]);
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $otherType->id,
    ]);

    $response = $this->actingAs($user)->get(route('tasks.index', ['procedure_type_id' => $matchingType->id]));

    $response->assertInertia(fn ($page) => $page
        ->has('tasks.data', 1)
        ->where('tasks.data.0.id', $matchingTask->id)
        ->where('filters.procedure_type_id', (string) $matchingType->id)
    );
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
