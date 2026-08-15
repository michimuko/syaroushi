<?php

namespace App\Services;

use App\Enums\RecurrenceType;
use App\Models\ProcedureType;
use Carbon\CarbonImmutable;

/**
 * procedure_types.recurrence_rule（JSON）を展開し、指定期間内の期限日一覧を算出する。
 *
 * 対応ルール：
 * - yearly: {month, day} が揃っている場合のみ、毎年その月日を期限日として展開する
 * - monthly: {interval_months, day?} が揃っている場合のみ、2000-01-01を起点に
 *   interval_months間隔で期限日を展開する（起点は各事務所の購読開始日等に依存しない
 *   固定値とし、どのタイミングでバッチを実行しても同じ日付集合を返す）
 *
 * one_time／custom、または必要なキーが揃っていないルールは、個別に手動でタスクを作成する
 * 運用のため展開対象外とし、空配列を返す（企画書8章・ProcedureTypeSeeder参照）。
 */
class RecurrenceRuleResolver
{
    private const MONTHLY_ANCHOR = '2000-01-01';

    /**
     * @return list<CarbonImmutable>
     */
    public function resolveDueDates(ProcedureType $procedureType, CarbonImmutable $from, CarbonImmutable $until): array
    {
        return match ($procedureType->recurrence_type) {
            RecurrenceType::Yearly => $this->resolveYearly($procedureType->recurrence_rule ?? [], $from, $until),
            RecurrenceType::Monthly => $this->resolveMonthly($procedureType->recurrence_rule ?? [], $from, $until),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $rule
     * @return list<CarbonImmutable>
     */
    private function resolveYearly(array $rule, CarbonImmutable $from, CarbonImmutable $until): array
    {
        if (! isset($rule['month'], $rule['day'])) {
            return [];
        }

        $month = (int) $rule['month'];
        $day = (int) $rule['day'];

        $dates = [];
        for ($year = $from->year; $year <= $until->year; $year++) {
            $date = $this->clampedDate($year, $month, $day);
            if ($date->between($from, $until)) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    /**
     * @param  array<string, mixed>  $rule
     * @return list<CarbonImmutable>
     */
    private function resolveMonthly(array $rule, CarbonImmutable $from, CarbonImmutable $until): array
    {
        if (! isset($rule['interval_months'])) {
            return [];
        }

        $intervalMonths = (int) $rule['interval_months'];
        if ($intervalMonths < 1) {
            return [];
        }

        $day = (int) ($rule['day'] ?? 1);

        $anchor = CarbonImmutable::parse(self::MONTHLY_ANCHOR);
        $cursor = $anchor;

        // 起点から検索窓の手前まで一気に進める
        if ($cursor->lt($from)) {
            $monthsSinceAnchor = $anchor->diffInMonths($from);
            $steps = intdiv((int) $monthsSinceAnchor, $intervalMonths);
            $cursor = $anchor->addMonths($steps * $intervalMonths);
        }

        $dates = [];
        while ($cursor->lte($until)) {
            $date = $this->clampedDate($cursor->year, $cursor->month, $day);
            if ($date->between($from, $until)) {
                $dates[] = $date;
            }
            $cursor = $cursor->addMonths($intervalMonths);
        }

        return $dates;
    }

    private function clampedDate(int $year, int $month, int $day): CarbonImmutable
    {
        $firstOfMonth = CarbonImmutable::createFromDate($year, $month, 1)->startOfDay();

        return $firstOfMonth->addDays(min($day, $firstOfMonth->daysInMonth) - 1);
    }
}
