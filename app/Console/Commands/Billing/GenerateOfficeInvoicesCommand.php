<?php

namespace App\Console\Commands\Billing;

use App\Models\BillingSetting;
use App\Models\Office;
use App\Models\OfficeInvoice;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 前月分の請求記録を事務所ごとに生成する（企画書11章：顧問先数に応じた従量制）。
 * 決済連携は行わず、あくまで「その月いくら請求すべきか」の記録を残すのみ。
 * トライアル終了日が前月の期間に一部でもかかる事務所、および利用停止中の事務所は対象外とする。
 * 同一期間の請求は一意制約（office_id, period_start）で重複生成を防ぐ。
 */
#[Signature('billing:generate-invoices')]
#[Description('前月分の事務所ごとの請求記録を顧問先数に応じて生成する（企画書11章）')]
class GenerateOfficeInvoicesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $periodStart = CarbonImmutable::today()->subMonthNoOverflow()->startOfMonth();
        $periodEnd = $periodStart->endOfMonth();
        $unitPrice = BillingSetting::current()->unit_price_per_client;

        $generatedCount = 0;
        $skippedCount = 0;

        Office::query()->where('is_active', true)->each(function (Office $office) use ($periodStart, $periodEnd, $unitPrice, &$generatedCount, &$skippedCount) {
            if ($office->trial_ends_at !== null && $office->trial_ends_at->greaterThan($periodStart)) {
                $skippedCount++;

                return;
            }

            if (OfficeInvoice::where('office_id', $office->id)->where('period_start', $periodStart)->exists()) {
                $skippedCount++;

                return;
            }

            $clientCount = $office->billableClientCount();

            OfficeInvoice::create([
                'office_id' => $office->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'client_count' => $clientCount,
                'unit_price' => $unitPrice,
                'amount' => $clientCount * $unitPrice,
                'generated_at' => now(),
            ]);
            $generatedCount++;
        });

        $this->info("{$periodStart->toDateString()}〜{$periodEnd->toDateString()}分の請求記録を{$generatedCount}件生成しました（対象外・生成済み{$skippedCount}件）。");

        return self::SUCCESS;
    }
}
