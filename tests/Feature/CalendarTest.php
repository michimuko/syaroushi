<?php

use App\Models\Client;
use App\Models\Office;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;

test('calendar screen can be rendered', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $response = $this->actingAs($user)->get(route('calendar.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Calendar/Index'));
});

test('calendar screen requires authentication', function () {
    $response = $this->get(route('calendar.index'));

    $response->assertRedirect(route('login'));
});

test('events endpoint returns only tasks within the requested date range for the current office', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $user = User::factory()->for($office)->create();

    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    $inRange = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'title' => '範囲内タスク',
        'due_date' => '2026-09-15',
    ]);
    ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'title' => '範囲外タスク',
        'due_date' => '2026-11-01',
    ]);

    $otherClient = Client::factory()->for($otherOffice)->create();
    ProcedureTask::factory()->for($otherOffice)->create([
        'client_id' => $otherClient->id,
        'procedure_type_id' => $procedureType->id,
        'due_date' => '2026-09-20',
    ]);

    $response = $this->actingAs($user)->getJson(route('calendar.events', [
        'start' => '2026-09-01',
        'end' => '2026-09-30',
    ]));

    $response->assertOk();
    $events = $response->json();

    expect($events)->toHaveCount(1)
        ->and($events[0]['id'])->toBe($inRange->id)
        ->and($events[0]['start'])->toBe('2026-09-15');
});

test('events endpoint colors overdue tasks red regardless of status', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    $overdue = ProcedureTask::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'status' => 'in_progress',
        'due_date' => now()->subDays(2)->toDateString(),
    ]);

    $response = $this->actingAs($user)->getJson(route('calendar.events', [
        'start' => now()->subDays(10)->toDateString(),
        'end' => now()->addDays(1)->toDateString(),
    ]));

    $event = collect($response->json())->firstWhere('id', $overdue->id);

    expect($event['backgroundColor'])->toBe('#dc2626')
        ->and($event['extendedProps']['isOverdue'])->toBeTrue();
});

test('events endpoint requires authentication', function () {
    $response = $this->getJson(route('calendar.events', [
        'start' => '2026-09-01',
        'end' => '2026-09-30',
    ]));

    $response->assertUnauthorized();
});
