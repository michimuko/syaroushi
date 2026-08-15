<?php

use App\Enums\ClientStatus;
use App\Enums\RecurrenceType;
use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\ClientProcedureSubscription;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;
use Carbon\CarbonImmutable;

it('generates a procedure task for an active subscription within the lookahead window', function () {
    CarbonImmutable::setTestNow('2026-06-01');

    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create([
        'assigned_user_id' => $staff->id,
        'status' => ClientStatus::Active,
    ]);
    $procedureType = ProcedureType::factory()->create([
        'name' => '算定基礎届（定時決定）',
        'recurrence_type' => RecurrenceType::Yearly,
        'recurrence_rule' => ['month' => 7, 'day' => 10],
    ]);
    ClientProcedureSubscription::factory()->create([
        'office_id' => $office->id,
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'is_active' => true,
    ]);

    $this->artisan('procedures:generate-upcoming')->assertExitCode(0);

    $task = ProcedureTask::query()->sole();
    expect($task->office_id)->toBe($office->id)
        ->and($task->client_id)->toBe($client->id)
        ->and($task->procedure_type_id)->toBe($procedureType->id)
        ->and($task->title)->toBe('算定基礎届（定時決定）')
        ->and($task->due_date->toDateString())->toBe('2026-07-10')
        ->and($task->status)->toBe(TaskStatus::NotStarted)
        ->and($task->assigned_user_id)->toBe($staff->id);

    CarbonImmutable::setTestNow();
});

it('does not create duplicate tasks when run twice', function () {
    CarbonImmutable::setTestNow('2026-06-01');

    $office = Office::factory()->create();
    $client = Client::factory()->for($office)->create(['status' => ClientStatus::Active]);
    $procedureType = ProcedureType::factory()->create([
        'recurrence_type' => RecurrenceType::Yearly,
        'recurrence_rule' => ['month' => 7, 'day' => 10],
    ]);
    ClientProcedureSubscription::factory()->create([
        'office_id' => $office->id,
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'is_active' => true,
    ]);

    $this->artisan('procedures:generate-upcoming');
    $this->artisan('procedures:generate-upcoming');

    expect(ProcedureTask::query()->count())->toBe(1);

    CarbonImmutable::setTestNow();
});

it('does not regenerate a duplicate task after its due_date has been corrected', function () {
    CarbonImmutable::setTestNow('2026-06-01');

    $office = Office::factory()->create();
    $client = Client::factory()->for($office)->create(['status' => ClientStatus::Active]);
    $procedureType = ProcedureType::factory()->create([
        'recurrence_type' => RecurrenceType::Yearly,
        'recurrence_rule' => ['month' => 7, 'day' => 10],
    ]);
    ClientProcedureSubscription::factory()->create([
        'office_id' => $office->id,
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'is_active' => true,
    ]);

    $this->artisan('procedures:generate-upcoming');

    $task = ProcedureTask::query()->sole();
    expect($task->original_due_date->toDateString())->toBe('2026-07-10');

    // 事務所側が期限を訂正（due_dateのみ変更、original_due_dateは維持）
    $task->update(['due_date' => '2026-07-17']);

    $this->artisan('procedures:generate-upcoming');

    expect(ProcedureTask::query()->count())->toBe(1);
    expect($task->fresh()->due_date->toDateString())->toBe('2026-07-17');

    CarbonImmutable::setTestNow();
});

it('skips inactive subscriptions, inactive clients, and inactive procedure types', function () {
    CarbonImmutable::setTestNow('2026-06-01');

    $office = Office::factory()->create();
    $activeType = ProcedureType::factory()->create([
        'recurrence_type' => RecurrenceType::Yearly,
        'recurrence_rule' => ['month' => 7, 'day' => 10],
    ]);
    $inactiveType = ProcedureType::factory()->inactive()->create([
        'recurrence_type' => RecurrenceType::Yearly,
        'recurrence_rule' => ['month' => 7, 'day' => 10],
    ]);

    $activeClient = Client::factory()->for($office)->create(['status' => ClientStatus::Active]);
    $inactiveClient = Client::factory()->for($office)->create(['status' => ClientStatus::Inactive]);

    ClientProcedureSubscription::factory()->create([
        'office_id' => $office->id,
        'client_id' => $activeClient->id,
        'procedure_type_id' => $activeType->id,
        'is_active' => false,
    ]);
    ClientProcedureSubscription::factory()->create([
        'office_id' => $office->id,
        'client_id' => $inactiveClient->id,
        'procedure_type_id' => $activeType->id,
        'is_active' => true,
    ]);
    ClientProcedureSubscription::factory()->create([
        'office_id' => $office->id,
        'client_id' => $activeClient->id,
        'procedure_type_id' => $inactiveType->id,
        'is_active' => true,
    ]);

    $this->artisan('procedures:generate-upcoming');

    expect(ProcedureTask::query()->count())->toBe(0);

    CarbonImmutable::setTestNow();
});

it('does not generate tasks for one_time procedure types', function () {
    CarbonImmutable::setTestNow('2026-06-01');

    $office = Office::factory()->create();
    $client = Client::factory()->for($office)->create(['status' => ClientStatus::Active]);
    $procedureType = ProcedureType::factory()->create([
        'recurrence_type' => RecurrenceType::OneTime,
        'recurrence_rule' => null,
    ]);
    ClientProcedureSubscription::factory()->create([
        'office_id' => $office->id,
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'is_active' => true,
    ]);

    $this->artisan('procedures:generate-upcoming');

    expect(ProcedureTask::query()->count())->toBe(0);

    CarbonImmutable::setTestNow();
});
