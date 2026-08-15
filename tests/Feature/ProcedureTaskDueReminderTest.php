<?php

use App\Models\Client;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;
use App\Notifications\ProcedureTaskDueReminder;
use NotificationChannels\WebPush\WebPushChannel;

it('only uses mail when the recipient has no push subscription', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => ProcedureType::factory()->create()->id,
    ]);

    $channels = (new ProcedureTaskDueReminder($task, 30))->via($staff);

    expect($channels)->toBe(['mail']);
});

it('adds the webpush channel when the recipient has a push subscription', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $staff->updatePushSubscription('https://fcm.googleapis.com/fcm/send/xyz', 'key', 'token');
    $client = Client::factory()->for($office)->create();
    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => ProcedureType::factory()->create()->id,
    ]);

    $channels = (new ProcedureTaskDueReminder($task, 30))->via($staff);

    expect($channels)->toBe(['mail', WebPushChannel::class]);
});
