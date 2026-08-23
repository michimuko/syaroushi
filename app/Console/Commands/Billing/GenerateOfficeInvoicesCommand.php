<?php

namespace App\Console\Commands\Billing;

use App\Models\Office;
use App\Models\OfficeInvoice;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 前月分の請求記録を事務所ごとに生成する（階層プラン制：割り当てられたbilling_planの月額固定、
 * またはoffices.custom_monthly_priceによる個別上書きを優先して請求額を決定する）。
 * 決済連携は行わず、あくまで「その月いくら請求すべきか」の記録を残すのみ。
 * トライアル終了日が前月の期間に一部でもかかる事務所、および利用停止中の事務所は対象外とする。
 * Stripeでの決済連携が開始済み（過去にStripe側でsubscriptionを作成しており、解約済みでない）な
 * 事務所は、Stripe側のSubscription/Invoiceが実際の請求の正とみなし、このバッチでは二重に請求記録を
 * 作らずスキップする。支払い失敗によるpast_due／unpaid等の一時的な異常状態でもStripe側が正であることに
 * 変わりはないためスキップ対象のまま（解約されcanceledになって初めてこのバッチの対象に戻る）。
 * プラン未割当かつ個別価格も未設定で金額が確定できない事務所は、エラーにせず警告ログを出してスキップする
 * （新規登録をブロックしない方針のため、料金未確定は運用上の注意喚起にとどめる）。
 * 同一期間の請求は一意制約（office_id, period_start）で重複生成を防ぐ。
 */
#[Signature('billing:generate-invoices')]
#[Description('前月分の事務所ごとの請求記録を契約プランの月額料金に基づいて生成する')]
class GenerateOfficeInvoicesCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $periodStart = CarbonImmutable::today()->subMonthNoOverflow()->startOfMonth();
        $periodEnd = $periodStart->endOfMonth();

        $generatedCount = 0;
        $skippedTrialCount = 0;
        $skippedDuplicateCount = 0;
        $skippedNoPriceCount = 0;
        $skippedStripeManagedCount = 0;

        Office::query()->where('is_active', true)->with('billingPlan')->each(function (Office $office) use (
            $periodStart,
            $periodEnd,
            &$generatedCount,
            &$skippedTrialCount,
            &$skippedDuplicateCount,
            &$skippedNoPriceCount,
            &$skippedStripeManagedCount
        ) {
            if ($office->trial_ends_at !== null && $office->trial_ends_at->greaterThan($periodStart)) {
                $skippedTrialCount++;

                return;
            }

            if ($office->isStripeManaged()) {
                $skippedStripeManagedCount++;

                return;
            }

            if (OfficeInvoice::where('office_id', $office->id)->where('period_start', $periodStart)->exists()) {
                $skippedDuplicateCount++;

                return;
            }

            $amount = $office->custom_monthly_price ?? $office->billingPlan?->monthly_price;

            if ($amount === null) {
                $skippedNoPriceCount++;
                $this->warn("事務所「{$office->name}」(ID:{$office->id})は料金プラン未設定または金額未確定のため請求記録を生成できませんでした。");

                return;
            }

            OfficeInvoice::create([
                'office_id' => $office->id,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'client_count' => $office->billableClientCount(),
                'user_count' => $office->currentUserCount(),
                'plan_name' => $office->billingPlan?->name ?? '個別設定',
                'amount' => $amount,
                'generated_at' => now(),
            ]);
            $generatedCount++;
        });

        $this->info("{$periodStart->toDateString()}〜{$periodEnd->toDateString()}分の請求記録を{$generatedCount}件生成しました（トライアル対象外{$skippedTrialCount}件、Stripe決済連携済み{$skippedStripeManagedCount}件、生成済み{$skippedDuplicateCount}件、料金未確定{$skippedNoPriceCount}件）。");

        return self::SUCCESS;
    }
}
