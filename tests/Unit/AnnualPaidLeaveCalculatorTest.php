<?php

use App\Services\CalcAssistant\AnnualPaidLeaveCalculator;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->calculator = new AnnualPaidLeaveCalculator;
});

it('calculates the full-time grant schedule from the hire date', function () {
    $schedule = $this->calculator->calculate(CarbonImmutable::parse('2024-04-01'), null);

    expect($schedule)->toHaveCount(7)
        ->and($schedule[0])->toBe([
            'grant_date' => '2024-10-01',
            'months_of_service' => 6,
            'service_label' => '6ヶ月',
            'days_granted' => 10,
        ])
        ->and($schedule[1]['grant_date'])->toBe('2025-10-01')
        ->and($schedule[1]['days_granted'])->toBe(11)
        ->and($schedule[6])->toBe([
            'grant_date' => '2030-10-01',
            'months_of_service' => 78,
            'service_label' => '6年6ヶ月',
            'days_granted' => 20,
        ]);
});

it('caps full-time grants at 20 days for years beyond 6.5', function () {
    $schedule = $this->calculator->calculate(CarbonImmutable::parse('2020-01-01'), null, 9);

    expect($schedule)->toHaveCount(9)
        ->and($schedule[7]['months_of_service'])->toBe(90)
        ->and($schedule[7]['service_label'])->toBe('7年6ヶ月')
        ->and($schedule[7]['days_granted'])->toBe(20)
        ->and($schedule[8]['months_of_service'])->toBe(102)
        ->and($schedule[8]['days_granted'])->toBe(20);
});

it('calculates proportional grants for a 4-day-per-week worker', function () {
    $schedule = $this->calculator->calculate(CarbonImmutable::parse('2024-04-01'), 4);

    expect(array_column($schedule, 'days_granted'))->toBe([7, 8, 9, 10, 12, 13, 15]);
});

it('calculates proportional grants for a 1-day-per-week worker', function () {
    $schedule = $this->calculator->calculate(CarbonImmutable::parse('2024-04-01'), 1);

    expect(array_column($schedule, 'days_granted'))->toBe([1, 2, 2, 2, 3, 3, 3]);
});

it('does not overflow into the next month when the hire date is on a month-end day', function () {
    // 2024-08-31 + 6ヶ月 = 2025-02-28（2025年は平年でうるう日なし）。
    // Carbonの単純なaddMonths()だと2/31が繰り上がり3/3になってしまうため、月末据え置きが必要。
    $schedule = $this->calculator->calculate(CarbonImmutable::parse('2024-08-31'), null, 1);

    expect($schedule[0]['grant_date'])->toBe('2025-02-28');
});

it('lands on the leap day when the milestone month has one', function () {
    // 2023-08-31 + 18ヶ月 = 2025-02-28、2024-08-31 + 18ヶ月 = 2026-02-28（うるう年の翌年なので28日）
    // だが 2022-08-31 + 18ヶ月 = 2024-02-29（2024年はうるう年）となることを確認する
    $schedule = $this->calculator->calculate(CarbonImmutable::parse('2022-08-31'), null, 2);

    expect($schedule[1]['grant_date'])->toBe('2024-02-29');
});

it('rejects an invalid weekly scheduled days value', function () {
    $this->calculator->calculate(CarbonImmutable::parse('2024-04-01'), 5);
})->throws(InvalidArgumentException::class);

it('rejects a grant count less than 1', function () {
    $this->calculator->calculate(CarbonImmutable::parse('2024-04-01'), null, 0);
})->throws(InvalidArgumentException::class);
