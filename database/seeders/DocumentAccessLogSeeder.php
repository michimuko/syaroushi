<?php

namespace Database\Seeders;

use App\Enums\DocumentAccessAction;
use App\Models\DocumentAccessLog;
use App\Models\ProcedureTaskDocument;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 収集済み書類のダウンロード履歴を作成する（企画書7.7章「取扱状況の把握」要件）。
 * アプリ側はダウンロード（署名付きURL発行）1種類のみをログ対象とする設計のため、
 * ダミーデータも同じ日時をずらした複数回のDownloadアクセスとして生成する。
 */
class DocumentAccessLogSeeder extends Seeder
{
    public function run(): void
    {
        ProcedureTaskDocument::query()->where('is_collected', true)->get()->each(function (ProcedureTaskDocument $document) {
            $user = User::query()->where('office_id', $document->office_id)->inRandomOrder()->first();

            if (! $user) {
                return;
            }

            DocumentAccessLog::query()->create([
                'office_id' => $document->office_id,
                'procedure_task_document_id' => $document->id,
                'user_id' => $user->id,
                'action' => DocumentAccessAction::Download,
                'accessed_at' => now()->subDays(2),
            ]);

            DocumentAccessLog::query()->create([
                'office_id' => $document->office_id,
                'procedure_task_document_id' => $document->id,
                'user_id' => $user->id,
                'action' => DocumentAccessAction::Download,
                'accessed_at' => now()->subDay(),
            ]);
        });
    }
}
