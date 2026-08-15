<?php

use App\Models\Client;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;

it('calculates a full-time paid leave grant schedule and echoes the task context', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($client)->for($procedureType)->create([
        'office_id' => $office->id,
        'assigned_user_id' => $owner->id,
        'title' => '有給休暇テストタスク',
    ]);

    $response = $this->actingAs($owner)->post(route('calc-assistant.paid-leave.calculate'), [
        'hire_date' => '2024-04-01',
        'task_id' => $task->id,
    ]);

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('CalcAssistant/PaidLeave')
        ->has('result.schedule', 7)
        ->where('result.schedule.0.grant_date', '2024-10-01')
        ->where('result.schedule.0.days_granted', 10)
        ->where('task.id', $task->id)
        ->where('task.title', '有給休暇テストタスク')
    );
});

it('calculates proportional grants when weekly_scheduled_days is given', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();

    $response = $this->actingAs($owner)->post(route('calc-assistant.paid-leave.calculate'), [
        'hire_date' => '2024-04-01',
        'weekly_scheduled_days' => 3,
    ]);

    $response->assertInertia(fn ($page) => $page
        ->where('result.schedule.0.days_granted', 5)
        ->where('task', null)
    );
});

it('rejects an invalid hire_date', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();

    $this->actingAs($owner)
        ->post(route('calc-assistant.paid-leave.calculate'), ['hire_date' => 'not-a-date'])
        ->assertSessionHasErrors('hire_date');
});

it('omits the task context when the requesting user cannot update the given task', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($client)->for($procedureType)->create([
        'office_id' => $office->id,
        'assigned_user_id' => null,
    ]);

    $response = $this->actingAs($staff)->post(route('calc-assistant.paid-leave.calculate'), [
        'hire_date' => '2024-04-01',
        'task_id' => $task->id,
    ]);

    $response->assertInertia(fn ($page) => $page->where('task', null));
});

it('saves the calculation result to the task calc_result column', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($client)->for($procedureType)->create([
        'office_id' => $office->id,
        'assigned_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)->put(route('tasks.calc-result.update', $task->id), [
        'type' => 'annual_paid_leave',
        'input' => ['hire_date' => '2024-04-01', 'weekly_scheduled_days' => null],
        'schedule' => [['grant_date' => '2024-10-01', 'months_of_service' => 6, 'service_label' => '6ヶ月', 'days_granted' => 10]],
    ]);

    $response->assertRedirect();
    $task->refresh();
    expect($task->calc_result['type'])->toBe('annual_paid_leave')
        ->and($task->calc_result['schedule'])->toHaveCount(1)
        ->and($task->calc_result['calculated_at'])->not->toBeNull();
});

it('denies a staff member from saving a calc result to a task they are not assigned to', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($client)->for($procedureType)->create([
        'office_id' => $office->id,
        'assigned_user_id' => null,
    ]);

    $this->actingAs($staff)->put(route('tasks.calc-result.update', $task->id), [
        'type' => 'annual_paid_leave',
        'input' => ['hire_date' => '2024-04-01'],
        'schedule' => [],
    ])->assertForbidden();
});

it('requires authentication to use the calc assistant', function () {
    $this->get(route('calc-assistant.index'))->assertRedirect(route('login'));
    $this->get(route('calc-assistant.paid-leave'))->assertRedirect(route('login'));
    $this->post(route('calc-assistant.paid-leave.calculate'), ['hire_date' => '2024-04-01'])
        ->assertRedirect(route('login'));
});

it('prevents saving a calc result to a task belonging to another office', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();
    $userA = User::factory()->for($officeA)->owner()->create();
    $client = Client::factory()->for($officeB)->create();
    $procedureType = ProcedureType::factory()->create();
    $foreignTask = ProcedureTask::factory()->for($client)->for($procedureType)->create([
        'office_id' => $officeB->id,
    ]);

    $this->actingAs($userA)->put(route('tasks.calc-result.update', $foreignTask->id), [
        'type' => 'annual_paid_leave',
        'input' => ['hire_date' => '2024-04-01'],
        'schedule' => [],
    ])->assertNotFound();
});
