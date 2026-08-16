<?php

use App\Enums\NotificationChannel;
use App\Models\Client;
use App\Models\NotificationLog;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;

function createTaskReminderLog(Office $office, User $recipient, NotificationChannel $channel = NotificationChannel::Email, int $leadDays = 7): NotificationLog
{
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($office)->for($client)->for($procedureType)->create();

    return NotificationLog::create([
        'office_id' => $office->id,
        'procedure_task_id' => $task->id,
        'recipient_user_id' => $recipient->id,
        'channel' => $channel,
        'lead_days' => $leadDays,
        'sent_at' => now(),
    ]);
}

it('rejects requests without a token', function () {
    $response = $this->getJson(route('api.desktop.notifications.index'));

    $response->assertUnauthorized();
});

it('rejects a token without the desktop-notifications ability', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $token = $user->createToken('other', ['some-other-ability']);

    $response = $this->withToken($token->plainTextToken)
        ->getJson(route('api.desktop.notifications.index'));

    $response->assertForbidden();
});

it('returns pending reminders and marks them as delivered so they are not returned twice', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $token = $user->createToken('desktop-app', ['desktop-notifications:read']);

    $log = createTaskReminderLog($office, $user);

    $response = $this->withToken($token->plainTextToken)
        ->getJson(route('api.desktop.notifications.index'));

    $response->assertOk();
    expect($response->json('notifications'))->toHaveCount(1);
    expect($response->json('notifications.0.procedure_task_id'))->toBe($log->procedure_task_id);
    expect($response->json('notifications.0.lead_days'))->toBe(7);

    expect(NotificationLog::query()->where('channel', NotificationChannel::Desktop)->count())->toBe(1);

    $secondResponse = $this->withToken($token->plainTextToken)
        ->getJson(route('api.desktop.notifications.index'));

    $secondResponse->assertOk();
    expect($secondResponse->json('notifications'))->toBe([]);
});

it('deduplicates a reminder that was logged for both email and webpush channels', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $token = $user->createToken('desktop-app', ['desktop-notifications:read']);

    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($office)->for($client)->for($procedureType)->create();

    foreach ([NotificationChannel::Email, NotificationChannel::WebPush] as $channel) {
        NotificationLog::create([
            'office_id' => $office->id,
            'procedure_task_id' => $task->id,
            'recipient_user_id' => $user->id,
            'channel' => $channel,
            'lead_days' => 7,
            'sent_at' => now(),
        ]);
    }

    $response = $this->withToken($token->plainTextToken)
        ->getJson(route('api.desktop.notifications.index'));

    $response->assertOk();
    expect($response->json('notifications'))->toHaveCount(1);
});

it('only returns reminders addressed to the token owner, never another office\'s recipient', function () {
    $officeA = Office::factory()->create();
    $userA = User::factory()->for($officeA)->create();
    $tokenA = $userA->createToken('desktop-app', ['desktop-notifications:read']);

    $officeB = Office::factory()->create();
    $userB = User::factory()->for($officeB)->create();
    createTaskReminderLog($officeB, $userB);

    $response = $this->withToken($tokenA->plainTextToken)
        ->getJson(route('api.desktop.notifications.index'));

    $response->assertOk();
    expect($response->json('notifications'))->toBe([]);
});
