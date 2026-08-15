<?php

use App\Models\Client;
use App\Models\ClientReport;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;
use App\Services\ClientReportStorage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

it('selects only tasks due within the given period', function () {
    $office = Office::factory()->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    $inPeriod = ProcedureTask::factory()->for($client)->for($procedureType)->create([
        'office_id' => $office->id,
        'due_date' => '2026-08-10',
    ]);
    ProcedureTask::factory()->for($client)->for($procedureType)->create([
        'office_id' => $office->id,
        'due_date' => '2026-09-10',
    ]);

    $tasks = $client->tasksDueBetween(
        CarbonImmutable::parse('2026-08-01'),
        CarbonImmutable::parse('2026-08-31'),
    );

    expect($tasks)->toHaveCount(1)
        ->and($tasks->first()->id)->toBe($inPeriod->id);
});

it('generates and stores a pdf report, recording who generated it', function () {
    Storage::fake('s3');

    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $client = Client::factory()->for($office)->create();

    $response = $this->actingAs($owner)->post(route('clients.reports.store', $client->id), [
        'period_start' => '2026-08-01',
        'period_end' => '2026-08-31',
        'comment' => '順調に進捗しています。',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $report = ClientReport::first();
    expect($report)->not->toBeNull()
        ->and($report->client_id)->toBe($client->id)
        ->and($report->office_id)->toBe($office->id)
        ->and($report->generated_by)->toBe($owner->id)
        ->and($report->comment)->toBe('順調に進捗しています。');

    Storage::disk('s3')->assertExists($report->pdf_path);
    expect(Storage::disk('s3')->get($report->pdf_path))->toStartWith('%PDF');
});

it('allows staff to generate a report only for their assigned client', function () {
    Storage::fake('s3');

    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $assignedClient = Client::factory()->for($office)->create(['assigned_user_id' => $staff->id]);
    $otherClient = Client::factory()->for($office)->create(['assigned_user_id' => null]);

    $this->actingAs($staff)
        ->post(route('clients.reports.store', $assignedClient->id), [
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
        ])
        ->assertRedirect();

    expect(ClientReport::where('client_id', $assignedClient->id)->count())->toBe(1);

    $this->actingAs($staff)
        ->post(route('clients.reports.store', $otherClient->id), [
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
        ])
        ->assertForbidden();
});

it('prevents access to another office\'s client reports', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();
    $userA = User::factory()->for($officeA)->owner()->create();
    $clientB = Client::factory()->for($officeB)->create();

    $this->actingAs($userA)
        ->get(route('clients.reports.index', $clientB->id))
        ->assertNotFound();

    $this->actingAs($userA)
        ->post(route('clients.reports.store', $clientB->id), [
            'period_start' => '2026-08-01',
            'period_end' => '2026-08-31',
        ])
        ->assertNotFound();
});

it('redirects to a temporary signed url on download', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $client = Client::factory()->for($office)->create();
    $report = ClientReport::factory()->for($office)->for($client)->create([
        'generated_by' => $owner->id,
        'pdf_path' => 'client-reports/report.pdf',
    ]);

    $this->mock(ClientReportStorage::class, function ($mock) use ($report) {
        $mock->shouldReceive('temporaryUrl')
            ->once()
            ->with($report->pdf_path)
            ->andReturn('https://example.test/signed-url');
    });

    $this->actingAs($owner)
        ->get(route('clients.reports.download', [$client->id, $report->id]))
        ->assertRedirect('https://example.test/signed-url');
});

it('denies a staff member from downloading a report for a client they are not assigned to', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create(['assigned_user_id' => null]);
    $report = ClientReport::factory()->for($office)->for($client)->create();

    $this->actingAs($staff)
        ->get(route('clients.reports.download', [$client->id, $report->id]))
        ->assertForbidden();
});
