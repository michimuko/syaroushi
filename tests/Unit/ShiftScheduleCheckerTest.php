<?php

use App\Services\CalcAssistant\ShiftScheduleChecker;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->checker = new ShiftScheduleChecker;
});

function shift(string $date, float $hours): array
{
    return ['date' => $date, 'hours' => $hours];
}

function consecutiveDaysFrom(string $start, int $count, float $hours = 8): array
{
    $shifts = [];
    $date = CarbonImmutable::parse($start);

    for ($i = 0; $i < $count; $i++) {
        $shifts[] = shift($date->addDays($i)->toDateString(), $hours);
    }

    return $shifts;
}

it('does not flag a day at exactly the 10-hour limit', function () {
    $result = $this->checker->check([shift('2026-04-06', 10)]);

    expect($result['days'][0]['exceeds_daily_limit'])->toBeFalse();
});

it('flags a day just over the 10-hour limit', function () {
    $result = $this->checker->check([shift('2026-04-06', 10.01)]);

    expect($result['days'][0]['exceeds_daily_limit'])->toBeTrue();
});

it('does not flag a week at exactly the 52-hour limit', function () {
    // 2026-04-06(月)〜04-12(日)の1週間、合計52時間
    $shifts = array_map(fn ($d) => shift($d, 52 / 6), [
        '2026-04-06', '2026-04-07', '2026-04-08', '2026-04-09', '2026-04-10', '2026-04-11',
    ]);

    $result = $this->checker->check($shifts);

    expect($result['weeks'])->toHaveCount(1)
        ->and($result['weeks'][0]['total_hours'])->toBe(52.0)
        ->and($result['weeks'][0]['exceeds_weekly_limit'])->toBeFalse();
});

it('flags a week just over the 52-hour limit', function () {
    $shifts = array_map(fn ($d) => shift($d, 9), [
        '2026-04-06', '2026-04-07', '2026-04-08', '2026-04-09', '2026-04-10', '2026-04-11',
    ]);
    $shifts[] = shift('2026-04-12', 1); // 週合計 55時間

    $result = $this->checker->check($shifts);

    expect($result['weeks'][0]['exceeds_weekly_limit'])->toBeTrue();
});

it('groups weeks starting on monday regardless of input order', function () {
    // 週をまたぐ2週分をあえて逆順で渡しても月曜起算で正しく集計される
    $result = $this->checker->check([
        shift('2026-04-13', 8), // 月曜（第2週）
        shift('2026-04-06', 8), // 月曜（第1週）
    ]);

    expect($result['weeks'])->toHaveCount(2)
        ->and($result['weeks'][0]['week_start'])->toBe('2026-04-06')
        ->and($result['weeks'][1]['week_start'])->toBe('2026-04-13');
});

it('does not flag exactly 6 consecutive work days', function () {
    $shifts = consecutiveDaysFrom('2026-04-06', 6);

    $result = $this->checker->check($shifts);

    expect($result['days'][5]['consecutive_work_days'])->toBe(6)
        ->and($result['days'][5]['exceeds_consecutive_limit'])->toBeFalse()
        ->and($result['summary']['exceeds_consecutive_limit'])->toBeFalse();
});

it('flags the 7th consecutive work day', function () {
    $shifts = consecutiveDaysFrom('2026-04-06', 7);

    $result = $this->checker->check($shifts);

    expect($result['days'][6]['consecutive_work_days'])->toBe(7)
        ->and($result['days'][6]['exceeds_consecutive_limit'])->toBeTrue()
        ->and($result['summary']['max_consecutive_work_days'])->toBe(7)
        ->and($result['summary']['exceeds_consecutive_limit'])->toBeTrue();
});

it('resets the consecutive streak on an explicit day off', function () {
    $shifts = consecutiveDaysFrom('2026-04-06', 6);
    $shifts[] = shift('2026-04-12', 0); // 休日
    $shifts[] = shift('2026-04-13', 8);

    $result = $this->checker->check($shifts);

    expect($result['days'][7]['consecutive_work_days'])->toBe(1);
});

it('resets the consecutive streak across a gap in the listed dates', function () {
    // 4/6〜4/8は勤務、4/9は明示的なデータなし（=カレンダー上の空白）、4/10から再開
    $shifts = [
        shift('2026-04-06', 8),
        shift('2026-04-07', 8),
        shift('2026-04-08', 8),
        shift('2026-04-10', 8),
    ];

    $result = $this->checker->check($shifts);

    expect($result['days'][3]['consecutive_work_days'])->toBe(1);
});

it('sorts unordered input by date before computing consecutive streaks', function () {
    $shifts = [
        shift('2026-04-08', 8),
        shift('2026-04-06', 8),
        shift('2026-04-07', 8),
    ];

    $result = $this->checker->check($shifts);

    expect($result['days'][0]['date'])->toBe('2026-04-06')
        ->and($result['days'][2]['date'])->toBe('2026-04-08')
        ->and($result['days'][2]['consecutive_work_days'])->toBe(3);
});

it('does not flag exactly 280 work days in the period', function () {
    $shifts = consecutiveWorkPattern(280);

    $result = $this->checker->check($shifts);

    expect($result['summary']['total_work_days'])->toBe(280)
        ->and($result['summary']['exceeds_280_days'])->toBeFalse();
});

it('flags 281 work days in the period', function () {
    $shifts = consecutiveWorkPattern(281);

    $result = $this->checker->check($shifts);

    expect($result['summary']['total_work_days'])->toBe(281)
        ->and($result['summary']['exceeds_280_days'])->toBeTrue();
});

/**
 * 6連勤+1休日を繰り返すことで、連続勤務日数の上限には抵触せずに指定した労働日数を作る。
 */
function consecutiveWorkPattern(int $workDays): array
{
    $shifts = [];
    $date = CarbonImmutable::parse('2026-01-01');
    $worked = 0;

    while ($worked < $workDays) {
        for ($i = 0; $i < 6 && $worked < $workDays; $i++) {
            $shifts[] = shift($date->toDateString(), 8);
            $date = $date->addDay();
            $worked++;
        }
        $shifts[] = shift($date->toDateString(), 0);
        $date = $date->addDay();
    }

    return $shifts;
}
