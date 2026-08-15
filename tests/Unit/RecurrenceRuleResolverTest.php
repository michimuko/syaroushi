<?php

use App\Enums\RecurrenceType;
use App\Models\ProcedureType;
use App\Services\RecurrenceRuleResolver;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->resolver = new RecurrenceRuleResolver;
});

it('resolves a yearly rule to the matching date within the window', function () {
    $procedureType = ProcedureType::factory()->make([
        'recurrence_type' => RecurrenceType::Yearly,
        'recurrence_rule' => ['month' => 7, 'day' => 10],
    ]);

    $dates = $this->resolver->resolveDueDates(
        $procedureType,
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2026-08-01'),
    );

    expect($dates)->toHaveCount(1)
        ->and($dates[0]->toDateString())->toBe('2026-07-10');
});

it('returns no dates for a yearly rule outside the window', function () {
    $procedureType = ProcedureType::factory()->make([
        'recurrence_type' => RecurrenceType::Yearly,
        'recurrence_rule' => ['month' => 7, 'day' => 10],
    ]);

    $dates = $this->resolver->resolveDueDates(
        $procedureType,
        CarbonImmutable::parse('2026-01-01'),
        CarbonImmutable::parse('2026-03-01'),
    );

    expect($dates)->toBeEmpty();
});

it('spans multiple years when the window is wide enough', function () {
    $procedureType = ProcedureType::factory()->make([
        'recurrence_type' => RecurrenceType::Yearly,
        'recurrence_rule' => ['month' => 7, 'day' => 10],
    ]);

    $dates = $this->resolver->resolveDueDates(
        $procedureType,
        CarbonImmutable::parse('2026-06-01'),
        CarbonImmutable::parse('2027-08-01'),
    );

    expect(array_map(fn ($date) => $date->toDateString(), $dates))
        ->toBe(['2026-07-10', '2027-07-10']);
});

it('returns no dates for a yearly rule missing month or day', function () {
    $procedureType = ProcedureType::factory()->make([
        'recurrence_type' => RecurrenceType::Yearly,
        'recurrence_rule' => ['note' => '有効期間は事業所ごとに異なる'],
    ]);

    $dates = $this->resolver->resolveDueDates(
        $procedureType,
        CarbonImmutable::parse('2026-01-01'),
        CarbonImmutable::parse('2026-12-31'),
    );

    expect($dates)->toBeEmpty();
});

it('resolves a monthly rule at the configured interval', function () {
    $procedureType = ProcedureType::factory()->make([
        'recurrence_type' => RecurrenceType::Monthly,
        'recurrence_rule' => ['interval_months' => 2, 'day' => 15],
    ]);

    $dates = $this->resolver->resolveDueDates(
        $procedureType,
        CarbonImmutable::parse('2026-08-01'),
        CarbonImmutable::parse('2026-12-31'),
    );

    // アンカー（2000-01-01）から2か月おきなので、8月から見て9月・11月が該当する
    expect(array_map(fn ($date) => $date->toDateString(), $dates))
        ->toBe(['2026-09-15', '2026-11-15']);
});

it('clamps a monthly day-of-month that does not exist in a shorter month', function () {
    $procedureType = ProcedureType::factory()->make([
        'recurrence_type' => RecurrenceType::Monthly,
        'recurrence_rule' => ['interval_months' => 1, 'day' => 31],
    ]);

    $dates = $this->resolver->resolveDueDates(
        $procedureType,
        CarbonImmutable::parse('2026-02-01'),
        CarbonImmutable::parse('2026-02-28'),
    );

    expect($dates)->toHaveCount(1)
        ->and($dates[0]->toDateString())->toBe('2026-02-28');
});

it('returns no dates for one_time and custom recurrence types', function () {
    $oneTime = ProcedureType::factory()->make([
        'recurrence_type' => RecurrenceType::OneTime,
        'recurrence_rule' => null,
    ]);
    $custom = ProcedureType::factory()->make([
        'recurrence_type' => RecurrenceType::Custom,
        'recurrence_rule' => ['note' => '個別管理'],
    ]);

    $window = [CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-12-31')];

    expect($this->resolver->resolveDueDates($oneTime, ...$window))->toBeEmpty()
        ->and($this->resolver->resolveDueDates($custom, ...$window))->toBeEmpty();
});
