<?php

use App\Models\Client;
use App\Models\ClientProcedureSubscription;
use App\Models\Office;
use App\Models\ProcedureType;
use App\Models\User;

test('edit screen includes procedure types and current subscriptions', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureTypeA = ProcedureType::factory()->create(['is_active' => true]);
    ProcedureType::factory()->create(['is_active' => false]); // 非アクティブは含まれない

    ClientProcedureSubscription::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureTypeA->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get(route('clients.edit', $client));

    $response->assertInertia(fn ($page) => $page
        ->has('procedureTypes', 1)
        ->where('subscribedProcedureTypeIds.0', $procedureTypeA->id)
    );
});

test('subscriptions can be updated to add and remove procedure types', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureTypeA = ProcedureType::factory()->create();
    $procedureTypeB = ProcedureType::factory()->create();

    // 事前にAだけ購読済み
    ClientProcedureSubscription::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureTypeA->id,
        'is_active' => true,
    ]);

    // Bだけを選択して送信 → Aは非アクティブ化、Bはアクティブ化
    $response = $this->actingAs($user)->put(
        route('clients.procedure-subscriptions.update', $client),
        ['procedure_type_ids' => [$procedureTypeB->id]],
    );

    $response->assertRedirect(route('clients.edit', $client));

    $subscriptionA = ClientProcedureSubscription::where('client_id', $client->id)
        ->where('procedure_type_id', $procedureTypeA->id)->first();
    $subscriptionB = ClientProcedureSubscription::where('client_id', $client->id)
        ->where('procedure_type_id', $procedureTypeB->id)->first();

    expect($subscriptionA->is_active)->toBeFalse()
        ->and($subscriptionB->is_active)->toBeTrue();
});

test('subscriptions can be updated to select none', function () {
    $office = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $client = Client::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    ClientProcedureSubscription::factory()->for($office)->create([
        'client_id' => $client->id,
        'procedure_type_id' => $procedureType->id,
        'is_active' => true,
    ]);

    $this->actingAs($user)->put(
        route('clients.procedure-subscriptions.update', $client),
        [],
    )->assertRedirect(route('clients.edit', $client));

    expect(ClientProcedureSubscription::where('client_id', $client->id)->first()->is_active)->toBeFalse();
});

test('subscriptions cannot be updated for a client in another office (404)', function () {
    $office = Office::factory()->create();
    $otherOffice = Office::factory()->create();
    $user = User::factory()->for($office)->create();
    $foreignClient = Client::factory()->for($otherOffice)->create();
    $procedureType = ProcedureType::factory()->create();

    $this->actingAs($user)->put(
        route('clients.procedure-subscriptions.update', $foreignClient),
        ['procedure_type_ids' => [$procedureType->id]],
    )->assertNotFound();
});
