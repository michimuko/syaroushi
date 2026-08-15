<?php

namespace Database\Seeders;

use App\Enums\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\ProcedureTask;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * タスクの期限アラート送信履歴を作成する（Phase4 自動生成バッチ＋通知の動作確認用）。
 * メール・Web Pushの両チャネル、複数のlead_daysパターンを混在させる。
 */
class NotificationLogSeeder extends Seeder
{
    public function run(): void
    {
        ProcedureTask::query()->with('client.office')->get()->each(function (ProcedureTask $task, int $index) {
            $recipient = User::query()->where('office_id', $task->office_id)->inRandomOrder()->first();

            if (! $recipient) {
                return;
            }

            NotificationLog::query()->create([
                'office_id' => $task->office_id,
                'procedure_task_id' => $task->id,
                'recipient_user_id' => $recipient->id,
                'channel' => $index % 3 === 0 ? NotificationChannel::WebPush : NotificationChannel::Email,
                'lead_days' => [90, 30, 7][$index % 3],
                'sent_at' => now()->subDays($index % 5 + 1),
            ]);
        });
    }
}
