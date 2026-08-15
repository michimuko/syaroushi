<?php

use App\Models\Client;
use App\Models\ClientProcedureSubscription;
use App\Models\Office;
use App\Models\ProcedureType;
use App\Models\User;
use Illuminate\Database\QueryException;

it('creates a subscription linking a client to a procedure type', function () {
    $office = Office::factory()->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    $subscription = ClientProcedureSubscription::factory()
        ->for($office)
        ->create([
            'client_id' => $client->id,
            'procedure_type_id' => $procedureType->id,
        ]);

    expect($subscription->client->id)->toBe($client->id)
        ->and($subscription->procedureType->id)->toBe($procedureType->id)
        ->and($subscription->is_active)->toBeTrue();
});

it('enforces uniqueness of client_id + procedure_type_id', function () {
    $office = Office::factory()->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    ClientProcedureSubscription::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
    ]);

    expect(fn () => ClientProcedureSubscription::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
    ]))->toThrow(QueryException::class);
});

it('scopes subscriptions to the authenticated user\'s office only', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();
    $userA = User::factory()->for($officeA)->create();

    $clientA = Client::factory()->for($officeA)->create();
    $clientB = Client::factory()->for($officeB)->create();
    $procedureType = ProcedureType::factory()->create();

    ClientProcedureSubscription::factory()->for($officeA)->create([
        'client_id' => $clientA->id,
        'procedure_type_id' => $procedureType->id,
    ]);
    ClientProcedureSubscription::factory()->for($officeB)->create([
        'client_id' => $clientB->id,
        'procedure_type_id' => $procedureType->id,
    ]);

    $this->actingAs($userA);

    expect(ClientProcedureSubscription::count())->toBe(1);
});

it('casts lead_days_override to an array', function () {
    $office = Office::factory()->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    $subscription = ClientProcedureSubscription::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'lead_days_override' => [14, 3],
    ]);

    expect($subscription->fresh()->lead_days_override)->toBe([14, 3]);
});
