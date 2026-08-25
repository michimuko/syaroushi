<?php

namespace App\Console\Commands\Offices;

use App\Enums\UserRole;
use App\Models\Office;
use App\Notifications\TrialDeletionFinalNotice;
use App\Notifications\TrialDeletionWarning;
use App\Notifications\TrialEndedWithoutPayment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 未払い放置時のデータ削除ポリシーに基づく3段階のメールリマインドを、対象事務所の
 * ownerへ送る（NotifyRetentionExpiryCommandと同じ「一度送ったら二度と送らない」設計、
 * *_notified_atで既読管理）。実際の削除実行はこのバッチでは行わない（運営者の手動確認、
 * Platform\OfficeController::softDelete/purge参照）。
 */
#[Signature('offices:notify-deletion-notices')]
#[Description('トライアル終了後もStripe未契約の事務所へ、データ削除ポリシーの案内メールを段階的に送る')]
class NotifyTrialDeletionNoticesCommand extends Command
{
    public function handle(): int
    {
        $trialEndedCount = $this->notifyStage(
            fn (Office $office) => $office->isPastTrialWithoutSubscription() && $office->trial_ended_notified_at === null,
            'trial_ended_notified_at',
            fn (Office $office) => new TrialEndedWithoutPayment($office),
        );

        $warningCount = $this->notifyStage(
            fn (Office $office) => $office->isInDeletionWarningPeriod() && $office->deletion_warning_notified_at === null,
            'deletion_warning_notified_at',
            fn (Office $office) => new TrialDeletionWarning($office),
        );

        $finalNoticeCount = $this->notifyStage(
            fn (Office $office) => $office->isDueForFinalDeletionNotice() && $office->deletion_final_notice_notified_at === null,
            'deletion_final_notice_notified_at',
            fn (Office $office) => new TrialDeletionFinalNotice($office),
        );

        $this->info("データ削除ポリシーの通知を送信しました（トライアル終了直後{$trialEndedCount}件、警告開始{$warningCount}件、最終リマインド{$finalNoticeCount}件）。");

        return self::SUCCESS;
    }

    /**
     * @param  callable(Office): bool  $matches
     * @param  callable(Office): \Illuminate\Notifications\Notification  $makeNotification
     */
    private function notifyStage(callable $matches, string $notifiedAtColumn, callable $makeNotification): int
    {
        $count = 0;

        Office::query()
            ->whereNotNull('trial_ends_at')
            ->whereNull('stripe_subscription_id')
            ->whereNull($notifiedAtColumn)
            ->with('users')
            ->each(function (Office $office) use ($matches, $notifiedAtColumn, $makeNotification, &$count) {
                if (! $matches($office)) {
                    return;
                }

                $owners = $office->users->where('role', UserRole::Owner);

                foreach ($owners as $owner) {
                    $owner->notify($makeNotification($office));
                }

                $office->update([$notifiedAtColumn => now()]);
                $count++;
            });

        return $count;
    }
}
