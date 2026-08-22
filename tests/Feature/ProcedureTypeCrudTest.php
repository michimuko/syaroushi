<?php

use App\Enums\Permission;
use App\Models\Office;
use App\Models\ProcedureType;
use App\Models\User;
use App\Services\RecurrenceRuleResolver;
use Carbon\CarbonImmutable;

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

test('a staff member with the manage_procedure_types permission can edit and update a procedure type', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)
        ->withPermissions([Permission::ManageProcedureTypes])
        ->create();
    $procedureType = ProcedureType::factory()->create();

    $this->actingAs($staff)
        ->get(route('procedure-types.edit', $procedureType))
        ->assertOk();

    $response = $this->actingAs($staff)->put(route('procedure-types.update', $procedureType), [
        'name' => $procedureType->name,
        'category' => $procedureType->category,
        'recurrence_type' => 'monthly',
        'default_lead_days' => [14, 7],
        'is_active' => true,
    ]);

    $response->assertRedirect(route('procedure-types.index'));
    expect($procedureType->fresh()->recurrence_type->value)->toBe('monthly');
});

test('a staff member with an unrelated permission still cannot edit a procedure type', function () {
    $office = Office::factory()->create();
    $staff = User::factory()->for($office)
        ->withPermissions([Permission::ManageCustomFields])
        ->create();
    $procedureType = ProcedureType::factory()->create();

    $this->actingAs($staff)
        ->get(route('procedure-types.edit', $procedureType))
        ->assertForbidden();
});

test('a yearly recurrence_rule is saved when month and day are both provided', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $procedureType = ProcedureType::factory()->create();

    $this->actingAs($owner)->put(route('procedure-types.update', $procedureType), [
        'name' => $procedureType->name,
        'category' => $procedureType->category,
        'recurrence_type' => 'yearly',
        'recurrence_rule' => ['month' => 7, 'day' => 10],
        'default_lead_days' => [30],
        'is_active' => true,
    ])->assertRedirect(route('procedure-types.index'));

    expect($procedureType->fresh()->recurrence_rule)->toEqual(['month' => 7, 'day' => 10]);
});

test('a yearly recurrence_rule is not saved when only month or day is provided (auto-generation stays disabled)', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $procedureType = ProcedureType::factory()->create();

    $this->actingAs($owner)->put(route('procedure-types.update', $procedureType), [
        'name' => $procedureType->name,
        'category' => $procedureType->category,
        'recurrence_type' => 'yearly',
        'recurrence_rule' => ['month' => 7, 'note' => '事業所ごとに満了日が異なるため個別管理'],
        'default_lead_days' => [30],
        'is_active' => true,
    ])->assertRedirect(route('procedure-types.index'));

    expect($procedureType->fresh()->recurrence_rule)->toBe(['note' => '事業所ごとに満了日が異なるため個別管理']);
});

test('a monthly recurrence_rule defaults day to 1 when only interval_months is provided', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $procedureType = ProcedureType::factory()->create();

    $this->actingAs($owner)->put(route('procedure-types.update', $procedureType), [
        'name' => $procedureType->name,
        'category' => $procedureType->category,
        'recurrence_type' => 'monthly',
        'recurrence_rule' => ['interval_months' => 2],
        'default_lead_days' => [14],
        'is_active' => true,
    ])->assertRedirect(route('procedure-types.index'));

    expect($procedureType->fresh()->recurrence_rule)->toEqual(['interval_months' => 2, 'day' => 1]);
});

test('recurrence_rule is cleared when switching to one_time or custom', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $procedureType = ProcedureType::factory()->create([
        'recurrence_type' => 'yearly',
        'recurrence_rule' => ['month' => 7, 'day' => 10],
    ]);

    $this->actingAs($owner)->put(route('procedure-types.update', $procedureType), [
        'name' => $procedureType->name,
        'category' => $procedureType->category,
        'recurrence_type' => 'one_time',
        'default_lead_days' => [14],
        'is_active' => true,
    ])->assertRedirect(route('procedure-types.index'));

    expect($procedureType->fresh()->recurrence_rule)->toBeNull();
});

test('an out-of-range month or day is rejected by validation', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $procedureType = ProcedureType::factory()->create();

    $response = $this->actingAs($owner)->put(route('procedure-types.update', $procedureType), [
        'name' => $procedureType->name,
        'category' => $procedureType->category,
        'recurrence_type' => 'yearly',
        'recurrence_rule' => ['month' => 13, 'day' => 10],
        'default_lead_days' => [14],
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors('recurrence_rule.month');
});

test('a saved recurrence_rule actually drives auto-generated due dates via RecurrenceRuleResolver', function () {
    $office = Office::factory()->create();
    $owner = User::factory()->for($office)->owner()->create();
    $procedureType = ProcedureType::factory()->create();

    $this->actingAs($owner)->put(route('procedure-types.update', $procedureType), [
        'name' => $procedureType->name,
        'category' => $procedureType->category,
        'recurrence_type' => 'yearly',
        'recurrence_rule' => ['month' => 7, 'day' => 10],
        'default_lead_days' => [30],
        'is_active' => true,
    ]);

    $dates = app(RecurrenceRuleResolver::class)->resolveDueDates(
        $procedureType->fresh(),
        CarbonImmutable::parse('2027-01-01'),
        CarbonImmutable::parse('2027-12-31'),
    );

    expect($dates)->toHaveCount(1)
        ->and($dates[0]->toDateString())->toBe('2027-07-10');
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
