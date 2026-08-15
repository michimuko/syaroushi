<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\ClientProcedureSubscription;
use App\Models\ProcedureTask;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * 顧問先の購読手続きごとにタスクを1件ずつ作成する。
 * 期限切れ・今週期限・今月期限・完了済みのパターンを一通り混在させ、
 * ダッシュボードの緊急度別サマリ・カレンダー表示を確認できるようにする。
 */
class ProcedureTaskSeeder extends Seeder
{
    private const PATTERNS = [
        ['status' => TaskStatus::NotStarted, 'due_days' => -5, 'completed' => false],
        ['status' => TaskStatus::InProgress, 'due_days' => 3, 'completed' => false],
        ['status' => TaskStatus::DocumentsCollected, 'due_days' => 20, 'completed' => false],
        ['status' => TaskStatus::Completed, 'due_days' => -10, 'completed' => true],
    ];

    public function run(): void
    {
        Client::query()->with(['office.users', 'procedureSubscriptions.procedureType'])->each(function (Client $client) {
            $users = $client->office->users;

            $client->procedureSubscriptions->each(function (ClientProcedureSubscription $subscription, int $index) use ($client, $users) {
                $pattern = self::PATTERNS[$index % count(self::PATTERNS)];
                $dueDate = Carbon::today()->addDays($pattern['due_days']);

                ProcedureTask::query()->create([
                    'office_id' => $client->office_id,
                    'client_id' => $client->id,
                    'procedure_type_id' => $subscription->procedure_type_id,
                    'title' => $subscription->procedureType->name,
                    'due_date' => $dueDate,
                    'status' => $pattern['status'],
                    'assigned_user_id' => $index % 2 === 0 ? $users->first()?->id : null,
                    'completed_at' => $pattern['completed'] ? $dueDate->copy()->addDays(2) : null,
                    'notes' => $index === 0 ? '至急対応が必要。' : null,
                ]);
            });
        });
    }
}
