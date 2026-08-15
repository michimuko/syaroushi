<?php

namespace App\Console\Commands\Procedures;

use App\Enums\NotificationChannel;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\ClientProcedureSubscription;
use App\Models\NotificationLog;
use App\Models\ProcedureTask;
use App\Models\User;
use App\Notifications\ProcedureTaskDueReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('procedures:send-reminders')]
#[Description('期限までの日数が通知ルールに一致するタスクにメール通知を送る（企画書8章）')]
class SendReminderNotificationsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = CarbonImmutable::today();

        $tasks = ProcedureTask::query()
            ->where('status', '!=', TaskStatus::Completed)
            ->whereDate('due_date', '>=', $today)
            ->with(['client', 'procedureType', 'assignedUser'])
            ->get();

        $sentCount = 0;

        foreach ($tasks as $task) {
            $leadDays = (int) $today->diffInDays($task->due_date);
            $leadDaysRule = $this->leadDaysFor($task);

            if (! in_array($leadDays, $leadDaysRule, true)) {
                continue;
            }

            foreach ($this->recipientsFor($task) as $recipient) {
                if ($this->alreadySent($task, $recipient, $leadDays)) {
                    continue;
                }

                $recipient->notify(new ProcedureTaskDueReminder($task, $leadDays));

                NotificationLog::create([
                    'office_id' => $task->office_id,
                    'procedure_task_id' => $task->id,
                    'recipient_user_id' => $recipient->id,
                    'channel' => NotificationChannel::Email,
                    'lead_days' => $leadDays,
                    'sent_at' => now(),
                ]);

                $sentCount++;
            }
        }

        $this->info("通知を{$sentCount}件送信しました。");

        return self::SUCCESS;
    }

    /**
     * @return list<int>
     */
    private function leadDaysFor(ProcedureTask $task): array
    {
        $subscription = ClientProcedureSubscription::query()
            ->where('client_id', $task->client_id)
            ->where('procedure_type_id', $task->procedure_type_id)
            ->first();

        return $subscription?->lead_days_override ?? $task->procedureType->default_lead_days ?? [];
    }

    /**
     * @return Collection<int, User>
     */
    private function recipientsFor(ProcedureTask $task): Collection
    {
        if ($task->assignedUser !== null) {
            return collect([$task->assignedUser]);
        }

        return User::query()
            ->where('office_id', $task->office_id)
            ->where('role', UserRole::Owner)
            ->get();
    }

    private function alreadySent(ProcedureTask $task, User $recipient, int $leadDays): bool
    {
        return NotificationLog::query()
            ->where('procedure_task_id', $task->id)
            ->where('recipient_user_id', $recipient->id)
            ->where('channel', NotificationChannel::Email)
            ->where('lead_days', $leadDays)
            ->exists();
    }
}
