<?php

use App\Enums\DocumentAccessAction;
use App\Models\Client;
use App\Models\DocumentAccessLog;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureTaskDocument;
use App\Models\ProcedureType;
use App\Models\User;
use App\Services\DocumentStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function makeTaskForDocuments(Office $office, ?User $assignedUser = null): ProcedureTask
{
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    return ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'assigned_user_id' => $assignedUser?->id,
    ]);
}

test('an owner can add a checklist item to any task in their office', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $task = makeTaskForDocuments($office);

    $response = $this->actingAs($owner)->post(route('tasks.documents.store', $task), [
        'name' => '雇用契約書の写し',
        'is_required' => true,
        'retention_years' => 3,
    ]);

    $response->assertRedirect();
    $document = ProcedureTaskDocument::sole();
    expect($document->name)->toBe('雇用契約書の写し')
        ->and($document->office_id)->toBe($office->id)
        ->and($document->retention_years)->toBe(3)
        ->and($document->is_collected)->toBeFalse();
});

test('a staff member can only add checklist items to their own assigned task', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $ownTask = makeTaskForDocuments($office, $staff);
    $othersTask = makeTaskForDocuments($office);

    $this->actingAs($staff)->post(route('tasks.documents.store', $ownTask), [
        'name' => '担当分の書類',
    ])->assertRedirect();
    expect(ProcedureTaskDocument::where('procedure_task_id', $ownTask->id)->count())->toBe(1);

    $this->actingAs($staff)->post(route('tasks.documents.store', $othersTask), [
        'name' => 'なりすまし追加',
    ])->assertForbidden();
});

test('uploading a file marks the document collected and calculates retention_until', function () {
    Storage::fake('s3');

    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $task = makeTaskForDocuments($office);
    $document = ProcedureTaskDocument::factory()->for($task, 'procedureTask')->for($office)->create([
        'retention_years' => 5,
    ]);

    $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

    $response = $this->actingAs($owner)->post(
        route('tasks.documents.upload', [$task, $document]),
        ['file' => $file],
    );

    $response->assertRedirect();
    $document->refresh();

    expect($document->is_collected)->toBeTrue()
        ->and($document->collected_at)->not->toBeNull()
        ->and($document->retention_until->toDateString())->toBe($document->collected_at->copy()->addYears(5)->toDateString())
        ->and($document->file_path)->not->toBeNull();

    Storage::disk('s3')->assertExists($document->file_path);
});

test('toggling collected without a file sets and clears collected_at/retention_until', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $task = makeTaskForDocuments($office);
    $document = ProcedureTaskDocument::factory()->for($task, 'procedureTask')->for($office)->create([
        'retention_years' => 2,
    ]);

    $this->actingAs($owner)->patch(route('tasks.documents.update', [$task, $document]), [
        'is_collected' => true,
    ])->assertRedirect();

    $document->refresh();
    expect($document->is_collected)->toBeTrue()
        ->and($document->collected_at)->not->toBeNull()
        ->and($document->retention_until->toDateString())->toBe($document->collected_at->copy()->addYears(2)->toDateString());

    $this->actingAs($owner)->patch(route('tasks.documents.update', [$task, $document]), [
        'is_collected' => false,
    ])->assertRedirect();

    $document->refresh();
    expect($document->is_collected)->toBeFalse()
        ->and($document->collected_at)->toBeNull()
        ->and($document->retention_until)->toBeNull();
});

test('a non-assigned staff member cannot upload, update, or delete a document', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $othersTask = makeTaskForDocuments($office);
    $document = ProcedureTaskDocument::factory()->for($othersTask, 'procedureTask')->for($office)->create();

    $file = UploadedFile::fake()->create('contract.pdf', 10, 'application/pdf');

    $this->actingAs($staff)->post(route('tasks.documents.upload', [$othersTask, $document]), ['file' => $file])
        ->assertForbidden();
    $this->actingAs($staff)->patch(route('tasks.documents.update', [$othersTask, $document]), ['is_collected' => true])
        ->assertForbidden();
    $this->actingAs($staff)->delete(route('tasks.documents.destroy', [$othersTask, $document]))
        ->assertForbidden();
});

test('deleting a document removes the stored file', function () {
    Storage::fake('s3');

    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $task = makeTaskForDocuments($office);
    $document = ProcedureTaskDocument::factory()->collected()->for($task, 'procedureTask')->for($office)->create();
    Storage::disk('s3')->put($document->file_path, 'dummy contents');

    $this->actingAs($owner)->delete(route('tasks.documents.destroy', [$task, $document]))
        ->assertRedirect();

    expect(ProcedureTaskDocument::find($document->id))->toBeNull();
    Storage::disk('s3')->assertMissing($document->file_path);
});

test('downloading redirects to a temporary signed url and requires a stored file', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $task = makeTaskForDocuments($office);

    $notCollected = ProcedureTaskDocument::factory()->for($task, 'procedureTask')->for($office)->create();
    $this->actingAs($owner)->get(route('tasks.documents.download', [$task, $notCollected]))
        ->assertNotFound();

    $collected = ProcedureTaskDocument::factory()->collected()->for($task, 'procedureTask')->for($office)->create();

    $this->mock(DocumentStorage::class, function ($mock) use ($collected) {
        $mock->shouldReceive('temporaryUrl')
            ->once()
            ->with($collected->file_path)
            ->andReturn('https://example.test/signed-url');
    });

    $this->actingAs($owner)->get(route('tasks.documents.download', [$task, $collected]))
        ->assertRedirect('https://example.test/signed-url');

    $log = DocumentAccessLog::sole();
    expect($log->procedure_task_document_id)->toBe($collected->id)
        ->and($log->user_id)->toBe($owner->id)
        ->and($log->office_id)->toBe($office->id)
        ->and($log->action)->toBe(DocumentAccessAction::Download);
});

test('each download creates its own access log entry', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $task = makeTaskForDocuments($office);
    $document = ProcedureTaskDocument::factory()->collected()->for($task, 'procedureTask')->for($office)->create();

    $this->mock(DocumentStorage::class, function ($mock) {
        $mock->shouldReceive('temporaryUrl')->twice()->andReturn('https://example.test/signed-url');
    });

    $this->actingAs($owner)->get(route('tasks.documents.download', [$task, $document]));
    $this->actingAs($owner)->get(route('tasks.documents.download', [$task, $document]));

    expect(DocumentAccessLog::where('procedure_task_document_id', $document->id)->count())->toBe(2);
});

test('a document belonging to a different task in the same office is not accessible via the wrong task url', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $taskA = makeTaskForDocuments($office);
    $taskB = makeTaskForDocuments($office);
    $documentOfTaskB = ProcedureTaskDocument::factory()->for($taskB, 'procedureTask')->for($office)->create();

    $this->actingAs($owner)->patch(route('tasks.documents.update', [$taskA, $documentOfTaskB]), [
        'is_collected' => true,
    ])->assertNotFound();
});

test('a document belonging to another office is not accessible', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $foreignTask = makeTaskForDocuments($otherOffice);
    $foreignDocument = ProcedureTaskDocument::factory()->for($foreignTask, 'procedureTask')->for($otherOffice)->create();

    $this->actingAs($user)->patch(route('tasks.documents.update', [$foreignTask, $foreignDocument]), [
        'is_collected' => true,
    ])->assertNotFound();
});
