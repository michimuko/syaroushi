<?php

use App\Enums\NotificationChannel;
use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\ClientProcedureSubscription;
use App\Models\NotificationLog;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;
use App\Notifications\ProcedureTaskDueReminder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-08-15');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('notifies the assigned user when the remaining days match the default lead days', function () {
    Notification::fake();

    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create(['default_lead_days' => [90, 30, 7]]);
    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
        'due_date' => CarbonImmutable::today()->addDays(30),
        'status' => TaskStatus::NotStarted,
    ]);

    $this->artisan('procedures:send-reminders')->assertExitCode(0);

    Notification::assertSentTo(
        $staff,
        ProcedureTaskDueReminder::class,
        fn ($notification) => $notification->procedureTask->is($task) && $notification->leadDays === 30,
    );
    expect(NotificationLog::query()->count())->toBe(1);
});

it('does not notify when the remaining days do not match any lead day rule', function () {
    Notification::fake();

    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create(['default_lead_days' => [90, 30, 7]]);
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
        'due_date' => CarbonImmutable::today()->addDays(15),
    ]);

    $this->artisan('procedures:send-reminders');

    Notification::assertNothingSent();
    expect(NotificationLog::query()->count())->toBe(0);
});

it('does not send a duplicate notification on a second run', function () {
    Notification::fake();

    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create(['default_lead_days' => [90, 30, 7]]);
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
        'due_date' => CarbonImmutable::today()->addDays(7),
    ]);

    $this->artisan('procedures:send-reminders');
    $this->artisan('procedures:send-reminders');

    Notification::assertSentToTimes($staff, ProcedureTaskDueReminder::class, 1);
    expect(NotificationLog::query()->count())->toBe(1);
});

it('falls back to office owners when the task has no assigned user', function () {
    Notification::fake();

    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    User::factory()->for($office)->create(); // staff、通知対象外
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create(['default_lead_days' => [90, 30, 7]]);
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => null,
        'due_date' => CarbonImmutable::today()->addDays(7),
    ]);

    $this->artisan('procedures:send-reminders');

    Notification::assertSentTo($owner, ProcedureTaskDueReminder::class);
    Notification::assertCount(1);
});

it('uses the subscription lead_days_override instead of the procedure type default', function () {
    Notification::fake();

    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create(['default_lead_days' => [90, 30, 7]]);
    ClientProcedureSubscription::factory()->create([
        'office_id' => $office->id,
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'lead_days_override' => [14],
    ]);
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
        'due_date' => CarbonImmutable::today()->addDays(14),
    ]);

    $this->artisan('procedures:send-reminders');

    Notification::assertSentTo($staff, ProcedureTaskDueReminder::class);
});

it('skips completed tasks', function () {
    Notification::fake();

    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create(['default_lead_days' => [90, 30, 7]]);
    ProcedureTask::factory()->for($office)->completed()->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
        'due_date' => CarbonImmutable::today()->addDays(7),
    ]);

    $this->artisan('procedures:send-reminders');

    Notification::assertNothingSent();
});

it('logs both mail and webpush channels when the recipient has a push subscription', function () {
    Notification::fake();

    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $staff->updatePushSubscription('https://fcm.googleapis.com/fcm/send/xyz', 'key', 'token');
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create(['default_lead_days' => [90, 30, 7]]);
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $staff->id,
        'due_date' => CarbonImmutable::today()->addDays(7),
    ]);

    $this->artisan('procedures:send-reminders');

    expect(NotificationLog::query()->count())->toBe(2)
        ->and(NotificationLog::query()->where('channel', NotificationChannel::Email)->count())->toBe(1)
        ->and(NotificationLog::query()->where('channel', NotificationChannel::WebPush)->count())->toBe(1);
});
