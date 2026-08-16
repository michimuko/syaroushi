<?php

namespace App\Http\Controllers;

use App\Enums\CustomFieldTarget;
use App\Models\CustomFieldDefinition;
use App\Models\ProcedureTask;
use App\Services\CustomFieldSpreadsheetFormatter;
use App\Services\Import\SpreadsheetWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 手続きタスク一覧をExcel(xlsx)/CSVへエクスポートする（Excel移行アシスタント、企画書6章）。
 * カスタムフィールド（企画書5-E）が定義されている場合は、その値を末尾列として追加出力する。
 */
class ProcedureTaskExportController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const HEADERS = ['顧問先名', '手続き種別名', 'タイトル', '期限日', 'ステータス', '担当者', 'メモ'];

    public function index(Request $request, SpreadsheetWriter $writer, CustomFieldSpreadsheetFormatter $formatter): StreamedResponse
    {
        $this->authorize('viewAny', ProcedureTask::class);

        $validated = $request->validate([
            'format' => 'nullable|in:xlsx,csv',
        ]);

        $office = Auth::user()->office;
        $definitions = CustomFieldDefinition::query()
            ->where('office_id', $office->id)
            ->where('target', CustomFieldTarget::ProcedureTask)
            ->orderBy('id')
            ->get();

        $headers = [...self::HEADERS, ...$definitions->pluck('label')->all()];

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
                ...$definitions->map(fn (CustomFieldDefinition $definition) => $formatter->formatForExport(
                    $definition->field_type,
                    $task->custom_fields[$definition->id] ?? null,
                ))->all(),
            ]);

        return $writer->export($validated['format'] ?? 'xlsx', '手続きタスク一覧', $headers, $rows);
    }
}
