<?php

use App\Models\Office;
use App\Models\ProcedureType;
use App\Models\User;

test('any authenticated user can view the procedure type index', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    ProcedureType::factory()->count(2)->create();

    $response = $this->actingAs($staff)->get(route('procedure-types.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('ProcedureTypes/Index')
        ->has('procedureTypes', 2)
    );
});

test('procedure type index requires authentication', function () {
    $response = $this->get(route('procedure-types.index'));

    $response->assertRedirect(route('login'));
});

test('an owner can view and update a procedure type', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $procedureType = ProcedureType::factory()->create(['default_lead_days' => [90, 30, 7]]);

    $this->actingAs($owner)
        ->get(route('procedure-types.edit', $procedureType))
        ->assertOk();

    $response = $this->actingAs($owner)->put(route('procedure-types.update', $procedureType), [
        'name' => $procedureType->name,
        'category' => $procedureType->category,
        'recurrence_type' => 'monthly',
        'default_lead_days' => [14, 7],
        'description' => '更新後の説明',
        'is_active' => false,
    ]);

    $response->assertRedirect(route('procedure-types.index'));

    $procedureType->refresh();
    expect($procedureType->recurrence_type->value)->toBe('monthly')
        ->and($procedureType->default_lead_days)->toBe([14, 7])
        ->and($procedureType->is_active)->toBeFalse();
});

test('default_lead_days values are cast to integers even when submitted as strings (real form-encoded HTTP behavior)', function () {
    // Pestのput()はPHP配列をそのまま渡すため型を保持してしまい、
    // 実際のHTTPフォーム送信（値は常に文字列になる）との差異に気づけなかった経緯があるため、
    // ここでは意図的に文字列の配列を送ってキャストの回帰を検証する。
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $procedureType = ProcedureType::factory()->create();

    $this->actingAs($owner)->put(route('procedure-types.update', $procedureType), [
        'name' => $procedureType->name,
        'category' => $procedureType->category,
        'recurrence_type' => 'yearly',
        'default_lead_days' => ['60', '14'],
        'is_active' => true,
    ]);

    expect($procedureType->fresh()->default_lead_days)->toBe([60, 14]);
});

test('a staff member cannot edit or update a procedure type (403)', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)->create();
    $procedureType = ProcedureType::factory()->create();

    $this->actingAs($staff)
        ->get(route('procedure-types.edit', $procedureType))
        ->assertForbidden();

    $response = $this->actingAs($staff)->put(route('procedure-types.update', $procedureType), [
        'name' => $procedureType->name,
        'category' => $procedureType->category,
        'recurrence_type' => 'monthly',
        'default_lead_days' => [1],
        'is_active' => true,
    ]);

    $response->assertForbidden();
    expect($procedureType->fresh()->recurrence_type)->toBe($procedureType->recurrence_type);
});

test('updating a procedure type affects it regardless of which office the owner belongs to (global master)', function () {
    $officeA = Office::factory()->create();
    $officeB = Office::factory()->create();
    $ownerA = User::factory()->for($officeA)->owner()->create();
    $procedureType = ProcedureType::factory()->create();

    $this->actingAs($ownerA)->put(route('procedure-types.update', $procedureType), [
        'name' => $procedureType->name,
        'category' => $procedureType->category,
        'recurrence_type' => 'custom',
        'default_lead_days' => [5],
        'is_active' => true,
    ])->assertRedirect(route('procedure-types.index'));

    $ownerB = User::factory()->for($officeB)->owner()->create();
    $this->actingAs($ownerB)->get(route('procedure-types.index'))
        ->assertInertia(fn ($page) => $page
            ->where('procedureTypes.0.recurrence_type', 'custom')
        );
});
