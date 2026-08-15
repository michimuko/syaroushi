<?php

use App\Models\Client;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureTaskDocument;
use App\Models\ProcedureType;
use App\Models\User;
use App\Notifications\DocumentRetentionExpired;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-08-15');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function makeDocumentWithRetention(Office $office, ?string $retentionUntil, ?string $retentionNotifiedAt = null): ProcedureTaskDocument
{
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();
    $task = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
    ]);

    return ProcedureTaskDocument::factory()->collected()->for($task, 'procedureTask')->for($office)->create([
        'retention_until' => $retentionUntil,
        'retention_notified_at' => $retentionNotifiedAt,
    ]);
}

it('notifies office owners for documents past their retention_until', function () {
    Notification::fake();

    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    User::factory()->for($office)->create(); // staff、通知対象外
    $document = makeDocumentWithRetention($office, CarbonImmutable::today()->subDay()->toDateString());

    $this->artisan('documents:notify-retention-expiry')->assertExitCode(0);

    Notification::assertSentTo(
        $owner,
        DocumentRetentionExpired::class,
        fn ($notification) => $notification->document->is($document),
    );
    Notification::assertCount(1);
    expect($document->fresh()->retention_notified_at)->not->toBeNull();
});

it('does not notify for documents whose retention_until has not passed yet', function () {
    Notification::fake();

    $office = Office::factory()->create();
    User::factory()->for($office)->owner()->create();
    makeDocumentWithRetention($office, CarbonImmutable::today()->addDay()->toDateString());

    $this->artisan('documents:notify-retention-expiry');

    Notification::assertNothingSent();
});

it('does not notify for documents without a retention_until', function () {
    Notification::fake();

    $office = Office::factory()->create();
    User::factory()->for($office)->owner()->create();
    makeDocumentWithRetention($office, null);

    $this->artisan('documents:notify-retention-expiry');

    Notification::assertNothingSent();
});

it('does not send a duplicate notification on a second run', function () {
    Notification::fake();

    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    makeDocumentWithRetention($office, CarbonImmutable::today()->subDay()->toDateString());

    $this->artisan('documents:notify-retention-expiry');
    $this->artisan('documents:notify-retention-expiry');

    Notification::assertSentToTimes($owner, DocumentRetentionExpired::class, 1);
});

it('does not re-notify an already-notified expired document', function () {
    Notification::fake();

    $office = Office::factory()->create();
    User::factory()->for($office)->owner()->create();
    makeDocumentWithRetention(
        $office,
        CarbonImmutable::today()->subDays(10)->toDateString(),
        CarbonImmutable::today()->subDays(9)->toDateString(),
    );

    $this->artisan('documents:notify-retention-expiry');

    Notification::assertNothingSent();
});
