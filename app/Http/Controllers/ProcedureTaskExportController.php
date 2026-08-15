<?php

namespace App\Http\Controllers;

use App\Models\ProcedureTask;
use App\Services\Import\SpreadsheetWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 手続きタスク一覧をExcel(xlsx)/CSVへエクスポートする（Excel移行アシスタント、企画書6章）。
 */
class ProcedureTaskExportController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const HEADERS = ['顧問先名', '手続き種別名', 'タイトル', '期限日', 'ステータス', '担当者', 'メモ'];

    public function index(Request $request, SpreadsheetWriter $writer): StreamedResponse
    {
        $this->authorize('viewAny', ProcedureTask::class);

        $validated = $request->validate([
            'format' => 'nullable|in:xlsx,csv',
        ]);

        $rows = ProcedureTask::query()
            ->with(['client:id,name', 'procedureType:id,name', 'assignedUser:id,name'])
            ->orderBy('due_date')
            ->get()
            ->map(fn (ProcedureTask $task) => [
                $task->client?->name,
                $task->procedureType?->name,
                $task->title,
                $task->due_date?->toDateString(),
                $task->status->label(),
                $task->assignedUser?->name,
                $task->notes,
            ]);

        return $writer->export($validated['format'] ?? 'xlsx', '手続きタスク一覧', self::HEADERS, $rows);
    }
}
