<?php

namespace App\Services\CalcAssistant;

use Carbon\CarbonImmutable;

/**
 * 勤務シフト表作成支援（企画書5-D・2.4章）。
 * 「社労士Tools」の1年単位の変形労働時間制カレンダー作成ツールが持つ
 * 「週52時間・1日10時間などの上限要件チェック機能」に相当する。
 * 1年単位の変形労働時間制（労働基準法32条の4）で守るべき上限を日次シフトから自動判定する：
 *   - 1日の労働時間10時間以内
 *   - 1週間（月曜起算）の労働時間52時間以内
 *   - 連続労働日数6日以内
 *   - 対象期間（3ヶ月超の場合）の労働日数280日以内
 */
class ShiftScheduleChecker
{
    private const DAILY_HOURS_LIMIT = 10;

    private const WEEKLY_HOURS_LIMIT = 52;

    private const CONSECUTIVE_WORK_DAYS_LIMIT = 6;

    private const ANNUAL_WORK_DAYS_LIMIT = 280;

    /**
     * @param  array<int, array{date: string, hours: float}>  $shifts  日付は任意の順序で渡せる（内部で昇順に並べ替える）
     * @return array{days: array<int, array<string, mixed>>, weeks: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    public function check(array $shifts): array
    {
        usort($shifts, fn (array $a, array $b) => $a['date'] <=> $b['date']);

        $days = [];
        $weeklyTotals = [];
        $consecutive = 0;
        $maxConsecutive = 0;
        $previousDate = null;
        $previousWorked = false;

        foreach ($shifts as $shift) {
            $date = CarbonImmutable::parse($shift['date']);
            $hours = (float) $shift['hours'];
            $worked = $hours > 0;
            $isAdjacentToPrevious = $previousDate !== null && $previousDate->addDay()->isSameDay($date);

            $consecutive = $worked
                ? ($isAdjacentToPrevious && $previousWorked ? $consecutive + 1 : 1)
                : 0;
            $maxConsecutive = max($maxConsecutive, $consecutive);

            $weekStart = $date->startOfWeek(CarbonImmutable::MONDAY)->toDateString();
            $weeklyTotals[$weekStart] = ($weeklyTotals[$weekStart] ?? 0) + $hours;

            $days[] = [
                'date' => $date->toDateString(),
                'hours' => $hours,
                'exceeds_daily_limit' => $hours > self::DAILY_HOURS_LIMIT,
                'consecutive_work_days' => $consecutive,
                'exceeds_consecutive_limit' => $consecutive > self::CONSECUTIVE_WORK_DAYS_LIMIT,
            ];

            $previousDate = $date;
            $previousWorked = $worked;
        }

        $weeks = [];
        foreach ($weeklyTotals as $weekStart => $total) {
            $weeks[] = [
                'week_start' => $weekStart,
                'total_hours' => round($total, 2),
                'exceeds_weekly_limit' => $total > self::WEEKLY_HOURS_LIMIT,
            ];
        }

        $totalWorkDays = count(array_filter($shifts, fn (array $s) => $s['hours'] > 0));

        return [
            'days' => $days,
            'weeks' => $weeks,
            'summary' => [
                'total_work_days' => $totalWorkDays,
                'exceeds_280_days' => $totalWorkDays > self::ANNUAL_WORK_DAYS_LIMIT,
                'max_consecutive_work_days' => $maxConsecutive,
                'exceeds_consecutive_limit' => $maxConsecutive > self::CONSECUTIVE_WORK_DAYS_LIMIT,
                'any_daily_violation' => (bool) array_filter($days, fn (array $d) => $d['exceeds_daily_limit']),
                'any_weekly_violation' => (bool) array_filter($weeks, fn (array $w) => $w['exceeds_weekly_limit']),
            ],
        ];
    }
}
