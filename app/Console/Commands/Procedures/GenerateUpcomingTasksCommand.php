<?php

namespace App\Console\Commands\Procedures;

use App\Enums\ClientStatus;
use App\Enums\TaskStatus;
use App\Models\ClientProcedureSubscription;
use App\Models\ProcedureTask;
use App\Services\RecurrenceRuleResolver;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('procedures:generate-upcoming')]
#[Description('周期ルールに基づき、先読み期間内のprocedure_tasksを自動生成する（企画書8章）')]
class GenerateUpcomingTasksCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(RecurrenceRuleResolver $resolver): int
    {
        $from = CarbonImmutable::today();
        $until = $from->addDays((int) config('procedures.generate_lookahead_days'));

        $subscriptions = ClientProcedureSubscription::query()
            ->where('is_active', true)
            ->whereHas('client', fn ($query) => $query->where('status', ClientStatus::Active))
            ->whereHas('procedureType', fn ($query) => $query->where('is_active', true))
            ->with(['client', 'procedureType'])
            ->get();

        $createdCount = 0;

        foreach ($subscriptions as $subscription) {
            $dueDates = $resolver->resolveDueDates($subscription->procedureType, $from, $until);

            foreach ($dueDates as $dueDate) {
                $task = ProcedureTask::query()->firstOrCreate(
                    [
                        'client_id' => $subscription->client_id,
                        'procedure_type_id' => $subscription->procedure_type_id,
                        'due_date' => $dueDate->toDateString(),
                    ],
                    [
                        'office_id' => $subscription->office_id,
                        'title' => $subscription->procedureType->name,
                        'status' => TaskStatus::NotStarted,
                        'assigned_user_id' => $subscription->client->assigned_user_id,
                    ],
                );

                if ($task->wasRecentlyCreated) {
                    $createdCount++;
                }
            }
        }

        $this->info("procedure_tasksを{$createdCount}件生成しました。");

        return self::SUCCESS;
    }
}
