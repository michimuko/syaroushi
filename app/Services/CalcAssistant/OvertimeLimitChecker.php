<?php

namespace App\Services\CalcAssistant;

/**
 * 時間外労働時間・36協定上限チェック（企画書5-D・2.4章）。
 * 「社労士Tools」の時間外労働時間計算ツールが対応していない、36協定の法定上限との
 * 自動照合・超過アラートが差別化ポイント。特別条項付き36協定でも超えられない上限
 * （労働基準法36条・厚生労働省ガイドライン）を月次データから自動判定する：
 *   - 単月100時間未満（時間外労働+休日労働の合計）
 *   - 2〜6ヶ月平均80時間以内（時間外労働+休日労働の合計）
 *   - 年720時間以内（時間外労働のみ）
 *   - 月45時間超は年6回まで（時間外労働のみ）
 */
class OvertimeLimitChecker
{
    private const MULTI_MONTH_WINDOW_SIZES = [2, 3, 4, 5, 6];

    private const MONTHLY_OVERTIME_LIMIT = 45;

    private const MONTHLY_COMBINED_LIMIT = 100;

    private const MULTI_MONTH_AVERAGE_LIMIT = 80;

    private const ANNUAL_OVERTIME_LIMIT = 720;

    private const MONTHLY_OVERTIME_EXCESS_ALLOWANCE = 6;

    /**
     * @param  array<int, array{month: string, overtime_hours: float, holiday_work_hours: float}>  $months  時系列順（古い月が先頭）
     * @return array{months: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    public function check(array $months): array
    {
        $combined = array_map(
            fn (array $m) => round($m['overtime_hours'] + $m['holiday_work_hours'], 2),
            $months,
        );

        $results = [];

        foreach ($months as $i => $month) {
            $maxAverage = null;

            foreach (self::MULTI_MONTH_WINDOW_SIZES as $size) {
                $windowStart = $i - $size + 1;

                if ($windowStart < 0) {
                    continue;
                }

                $average = round(array_sum(array_slice($combined, $windowStart, $size)) / $size, 2);
                $maxAverage = $maxAverage === null ? $average : max($maxAverage, $average);
            }

            $results[] = [
                'month' => $month['month'],
                'overtime_hours' => $month['overtime_hours'],
                'holiday_work_hours' => $month['holiday_work_hours'],
                'combined_hours' => $combined[$i],
                'exceeds_45' => $month['overtime_hours'] > self::MONTHLY_OVERTIME_LIMIT,
                'exceeds_100_combined' => $combined[$i] >= self::MONTHLY_COMBINED_LIMIT,
                'multi_month_average' => $maxAverage,
                'exceeds_80_average' => $maxAverage !== null && $maxAverage > self::MULTI_MONTH_AVERAGE_LIMIT,
            ];
        }

        $monthsExceeding45Count = count(array_filter($results, fn (array $r) => $r['exceeds_45']));
        $annualOvertimeHours = round(array_sum(array_column($months, 'overtime_hours')), 2);

        return [
            'months' => $results,
            'summary' => [
                'months_exceeding_45_count' => $monthsExceeding45Count,
                'months_exceeding_45_within_allowance' => $monthsExceeding45Count <= self::MONTHLY_OVERTIME_EXCESS_ALLOWANCE,
                'annual_overtime_hours' => $annualOvertimeHours,
                'annual_overtime_within_720' => $annualOvertimeHours <= self::ANNUAL_OVERTIME_LIMIT,
                'any_month_reaches_100_combined' => (bool) array_filter($results, fn (array $r) => $r['exceeds_100_combined']),
                'any_multi_month_average_exceeds_80' => (bool) array_filter($results, fn (array $r) => $r['exceeds_80_average']),
            ],
        ];
    }
}
