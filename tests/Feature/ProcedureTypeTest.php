<?php

use App\Enums\RecurrenceType;
use App\Models\ProcedureType;
use Database\Seeders\ProcedureTypeSeeder;
use Illuminate\Support\Facades\Schema;

it('creates a procedure type with casts', function () {
    $procedureType = ProcedureType::factory()->create([
        'name' => '算定基礎届（定時決定）',
        'recurrence_type' => RecurrenceType::Yearly,
        'default_lead_days' => [90, 30, 7],
    ]);

    expect($procedureType->recurrence_type)->toBe(RecurrenceType::Yearly)
        ->and($procedureType->default_lead_days)->toBe([90, 30, 7])
        ->and($procedureType->is_active)->toBeTrue();
});

it('is not scoped to any office (global master)', function () {
    ProcedureType::factory()->create();

    // office_idカラム自体が存在しないことを確認（企画書7.6章）
    expect(Schema::hasColumn('procedure_types', 'office_id'))->toBeFalse();
});

it('seeds the standard set of procedure types', function () {
    $this->seed(ProcedureTypeSeeder::class);

    expect(ProcedureType::count())->toBe(15)
        ->and(ProcedureType::where('name', '算定基礎届（定時決定）')->exists())->toBeTrue()
        ->and(ProcedureType::where('is_active', true)->count())->toBe(15);
});

it('seeder is idempotent (running twice does not duplicate rows)', function () {
    $this->seed(ProcedureTypeSeeder::class);
    $this->seed(ProcedureTypeSeeder::class);

    expect(ProcedureType::count())->toBe(15);
});
