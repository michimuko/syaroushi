<?php

namespace App\Console\Commands\Documents;

use App\Enums\UserRole;
use App\Models\ProcedureTaskDocument;
use App\Models\User;
use App\Notifications\DocumentRetentionExpired;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 保存期限(retention_until)を過ぎた書類を検出し、ownerに通知する（企画書7.7章）。
 * 自動削除は行わない。同じ書類を毎日重複通知しないよう、通知済みの書類は
 * retention_notified_atで既読管理する（削除も再収集もされない限り再通知しない）。
 */
#[Signature('documents:notify-retention-expiry')]
#[Description('保存期限を過ぎた書類を検出し、ownerに通知する（企画書7.7章）')]
class NotifyRetentionExpiryCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = CarbonImmutable::today();

        $documents = ProcedureTaskDocument::query()
            ->whereNotNull('retention_until')
            ->whereDate('retention_until', '<', $today)
            ->whereNull('retention_notified_at')
            ->with(['procedureTask.client'])
            ->get();

        $notifiedCount = 0;

        foreach ($documents as $document) {
            $owners = User::query()
                ->where('office_id', $document->office_id)
                ->where('role', UserRole::Owner)
                ->get();

            foreach ($owners as $owner) {
                $owner->notify(new DocumentRetentionExpired($document));
            }

            $document->update(['retention_notified_at' => now()]);
            $notifiedCount++;
        }

        $this->info("保存期限切れの通知を{$notifiedCount}件の書類について送信しました。");

        return self::SUCCESS;
    }
}
