<?php

namespace App\Services\CalcAssistant;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * 年次有給休暇の自動付与日数計算（企画書5-D・2.4章）。
 * 労働基準法39条・同法施行規則別表第一に基づく、入社日基準での比例付与対応版。
 * 「社労士Tools」の年次有給休暇管理簿テンプレートが非対応と明記している比例付与
 * （週所定労働日数が少ないパート・アルバイト等）への対応が差別化ポイント。
 *
 * 各基準日で全労働日の8割以上出勤していることが前提（本計算はその判定は行わない）。
 */
class AnnualPaidLeaveCalculator
{
    /**
     * 基準日の勤続月数（6ヶ月, 1年6ヶ月, 2年6ヶ月, ...）。7番目（78ヶ月=6年6ヶ月）以降は上限で据え置き。
     */
    private const MILESTONE_MONTHS = [6, 18, 30, 42, 54, 66, 78];

    /**
     * フルタイム（週30時間以上または週5日以上）の付与日数。
     */
    private const FULL_TIME_DAYS = [10, 11, 12, 14, 16, 18, 20];

    /**
     * 比例付与（労基則別表第一）：週所定労働日数(1〜4)ごとの付与日数。
     */
    private const PROPORTIONAL_DAYS = [
        4 => [7, 8, 9, 10, 12, 13, 15],
        3 => [5, 6, 6, 8, 9, 10, 11],
        2 => [3, 4, 4, 5, 6, 6, 7],
        1 => [1, 2, 2, 2, 3, 3, 3],
    ];

    /**
     * @return array<int, array{grant_date: string, months_of_service: int, service_label: string, days_granted: int}>
     */
    public function calculate(CarbonImmutable $hireDate, ?int $weeklyScheduledDays, int $grantCount = 7): array
    {
        if ($grantCount < 1) {
            throw new InvalidArgumentException('grantCount must be at least 1.');
        }

        if ($weeklyScheduledDays !== null && ! in_array($weeklyScheduledDays, [1, 2, 3, 4], true)) {
            throw new InvalidArgumentException('weeklyScheduledDays must be null (full-time) or 1-4.');
        }

        $daysTable = $weeklyScheduledDays === null
            ? self::FULL_TIME_DAYS
            : self::PROPORTIONAL_DAYS[$weeklyScheduledDays];

        $schedule = [];

        for ($i = 0; $i < $grantCount; $i++) {
            $milestoneIndex = min($i, count(self::MILESTONE_MONTHS) - 1);
            $months = $i < count(self::MILESTONE_MONTHS)
                ? self::MILESTONE_MONTHS[$i]
                : self::MILESTONE_MONTHS[count(self::MILESTONE_MONTHS) - 1] + ($i - count(self::MILESTONE_MONTHS) + 1) * 12;

            // 月末日起算（例：1/31入社）で存在しない日付に繰り上がらないよう、月末据え置きのnoOverflowで加算する
            $grantDate = $hireDate->addMonthsNoOverflow($months);

            $schedule[] = [
                'grant_date' => $grantDate->toDateString(),
                'months_of_service' => $months,
                'service_label' => $this->serviceLabel($months),
                'days_granted' => $daysTable[$milestoneIndex],
            ];
        }

        return $schedule;
    }

    private function serviceLabel(int $months): string
    {
        $years = intdiv($months, 12);
        $remainder = $months % 12;

        if ($years === 0) {
            return "{$remainder}ヶ月";
        }

        return $remainder === 0 ? "{$years}年" : "{$years}年{$remainder}ヶ月";
    }
}
