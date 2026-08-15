<?php

namespace Database\Seeders;

use App\Models\ProcedureTask;
use App\Models\ProcedureTaskDocument;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * タスクごとに書類チェックリストを作成する。未収集・収集済み（保存期限内）・
 * 保存期限切れ未通知・保存期限切れ通知済みのパターンを混在させ、
 * 書類チェックリスト画面と `documents:notify-retention-expiry` バッチの
 * 動作確認（企画書7.7章）ができるようにする。
 */
class ProcedureTaskDocumentSeeder extends Seeder
{
    public function run(): void
    {
        ProcedureTask::query()->get()->each(function (ProcedureTask $task, int $index) {
            $base = [
                'office_id' => $task->office_id,
                'procedure_task_id' => $task->id,
                'is_required' => true,
            ];

            match ($index % 4) {
                0 => ProcedureTaskDocument::query()->create([
                    ...$base,
                    'name' => '賃金台帳',
                    'is_collected' => false,
                ]),
                1 => ProcedureTaskDocument::query()->create([
                    ...$base,
                    'name' => '出勤簿',
                    'is_collected' => true,
                    'collected_at' => now(),
                    'file_path' => 'procedure-task-documents/'.fake()->uuid().'.pdf',
                    'retention_years' => 5,
                    'retention_until' => Carbon::today()->addYears(5),
                ]),
                2 => ProcedureTaskDocument::query()->create([
                    ...$base,
                    'name' => '雇用契約書',
                    'is_collected' => true,
                    'collected_at' => Carbon::today()->subYears(5)->subDays(10),
                    'file_path' => 'procedure-task-documents/'.fake()->uuid().'.pdf',
                    'retention_years' => 5,
                    'retention_until' => Carbon::today()->subDays(10),
                    'retention_notified_at' => null,
                ]),
                default => ProcedureTaskDocument::query()->create([
                    ...$base,
                    'name' => '労働条件通知書',
                    'is_collected' => true,
                    'collected_at' => Carbon::today()->subYears(7),
                    'file_path' => 'procedure-task-documents/'.fake()->uuid().'.pdf',
                    'retention_years' => 3,
                    'retention_until' => Carbon::today()->subDays(30),
                    'retention_notified_at' => Carbon::today()->subDays(29),
                ]),
            };
        });
    }
}
