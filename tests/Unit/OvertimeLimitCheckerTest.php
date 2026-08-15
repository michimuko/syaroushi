<?php

use App\Services\CalcAssistant\OvertimeLimitChecker;

beforeEach(function () {
    $this->checker = new OvertimeLimitChecker;
});

function month(string $label, float $overtime, float $holiday = 0): array
{
    return ['month' => $label, 'overtime_hours' => $overtime, 'holiday_work_hours' => $holiday];
}

it('does not flag a month at exactly 45 overtime hours', function () {
    $result = $this->checker->check([month('2026-04', 45)]);

    expect($result['months'][0]['exceeds_45'])->toBeFalse();
});

it('flags a month just over 45 overtime hours', function () {
    $result = $this->checker->check([month('2026-04', 45.01)]);

    expect($result['months'][0]['exceeds_45'])->toBeTrue();
});

it('does not flag a combined total just under 100 hours', function () {
    $result = $this->checker->check([month('2026-04', 60, 39.99)]);

    expect($result['months'][0]['combined_hours'])->toBe(99.99)
        ->and($result['months'][0]['exceeds_100_combined'])->toBeFalse();
});

it('flags a combined total of exactly 100 hours (100時間未満が上限のため)', function () {
    $result = $this->checker->check([month('2026-04', 60, 40)]);

    expect($result['months'][0]['exceeds_100_combined'])->toBeTrue();
});

it('does not compute a multi-month average until enough months exist', function () {
    $result = $this->checker->check([month('2026-04', 90), month('2026-05', 90)]);

    // 2ヶ月目時点で2ヶ月平均(90)は計算されるが、3〜6ヶ月平均はまだ計算できない
    expect($result['months'][0]['multi_month_average'])->toBeNull()
        ->and($result['months'][1]['multi_month_average'])->toBe(90.0);
});

it('does not flag a 2-month average of exactly 80 hours', function () {
    $result = $this->checker->check([month('2026-04', 80), month('2026-05', 80)]);

    expect($result['months'][1]['multi_month_average'])->toBe(80.0)
        ->and($result['months'][1]['exceeds_80_average'])->toBeFalse();
});

it('flags when any valid window average exceeds 80 hours', function () {
    // 2ヶ月平均は(100+60)/2=80（違反なし）だが、直近の値も加味した幅広い窓で超過を検知する
    $result = $this->checker->check([
        month('2026-01', 100),
        month('2026-02', 100),
        month('2026-03', 60),
    ]);

    // 3ヶ月平均 = (100+100+60)/3 = 86.67 > 80 で違反
    expect($result['months'][2]['multi_month_average'])->toBe(round((100 + 100 + 60) / 3, 2))
        ->and($result['months'][2]['exceeds_80_average'])->toBeTrue();
});

it('counts months exceeding 45 hours and allows up to 6 per year', function () {
    $months = array_map(fn ($i) => month(sprintf('2026-%02d', $i), $i <= 6 ? 50 : 30), range(1, 12));

    $result = $this->checker->check($months);

    expect($result['summary']['months_exceeding_45_count'])->toBe(6)
        ->and($result['summary']['months_exceeding_45_within_allowance'])->toBeTrue();
});

it('flags when more than 6 months exceed 45 hours in a year', function () {
    $months = array_map(fn ($i) => month(sprintf('2026-%02d', $i), $i <= 7 ? 50 : 30), range(1, 12));

    $result = $this->checker->check($months);

    expect($result['summary']['months_exceeding_45_count'])->toBe(7)
        ->and($result['summary']['months_exceeding_45_within_allowance'])->toBeFalse();
});

it('does not flag an annual overtime total of exactly 720 hours', function () {
    $months = array_map(fn ($i) => month(sprintf('2026-%02d', $i), 60), range(1, 12));

    $result = $this->checker->check($months);

    expect($result['summary']['annual_overtime_hours'])->toBe(720.0)
        ->and($result['summary']['annual_overtime_within_720'])->toBeTrue();
});

it('flags an annual overtime total just over 720 hours', function () {
    $months = array_map(fn ($i) => month(sprintf('2026-%02d', $i), $i === 1 ? 60.01 : 60), range(1, 12));

    $result = $this->checker->check($months);

    expect($result['summary']['annual_overtime_within_720'])->toBeFalse();
});

it('summarizes whether any month reaches the 100-hour combined threshold', function () {
    $result = $this->checker->check([month('2026-04', 60, 40), month('2026-05', 10)]);

    expect($result['summary']['any_month_reaches_100_combined'])->toBeTrue();
});
