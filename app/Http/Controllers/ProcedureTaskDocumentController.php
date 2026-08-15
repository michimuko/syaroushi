<?php

namespace App\Http\Controllers;

use App\Models\ProcedureTask;
use App\Models\ProcedureTaskDocument;
use App\Services\DocumentStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProcedureTaskDocumentController extends Controller
{
    public function store(Request $request, ProcedureTask $task): RedirectResponse
    {
        $this->authorize('create', [ProcedureTaskDocument::class, $task]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_required' => 'boolean',
            'retention_years' => 'nullable|integer|min:1|max:100',
        ]);

        $task->documents()->create([
            'name' => $validated['name'],
            'is_required' => $validated['is_required'] ?? true,
            'retention_years' => $validated['retention_years'] ?? null,
        ]);

        return back()->with('success', '書類チェックリストに追加しました。');
    }

    public function uploadFile(Request $request, ProcedureTask $task, ProcedureTaskDocument $document, DocumentStorage $documentStorage): RedirectResponse
    {
        $document = $this->scoped($task, $document);
        $this->authorize('update', $document);

        $validated = $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx',
        ]);

        if ($document->file_path) {
            $documentStorage->delete($document->file_path);
        }

        $path = $documentStorage->store($document->office_id, $validated['file']);

        $document->update([
            'file_path' => $path,
            ...$this->collectedAttributes($document, collected: true),
        ]);

        return back()->with('success', '書類をアップロードしました。');
    }

    public function update(Request $request, ProcedureTask $task, ProcedureTaskDocument $document): RedirectResponse
    {
        $document = $this->scoped($task, $document);
        $this->authorize('update', $document);

        $validated = $request->validate([
            'is_collected' => 'required|boolean',
            'retention_years' => 'nullable|integer|min:1|max:100',
        ]);

        $document->retention_years = $validated['retention_years'] ?? $document->retention_years;

        $document->update([
            'retention_years' => $document->retention_years,
            ...$this->collectedAttributes($document, collected: $validated['is_collected']),
        ]);

        return back()->with('success', '書類チェックリストを更新しました。');
    }

    public function destroy(ProcedureTask $task, ProcedureTaskDocument $document, DocumentStorage $documentStorage): RedirectResponse
    {
        $document = $this->scoped($task, $document);
        $this->authorize('delete', $document);

        if ($document->file_path) {
            $documentStorage->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', '書類チェックリストから削除しました。');
    }

    public function download(ProcedureTask $task, ProcedureTaskDocument $document, DocumentStorage $documentStorage): RedirectResponse
    {
        $document = $this->scoped($task, $document);
        $this->authorize('view', $document);

        abort_if(! $document->file_path, 404);

        return redirect()->away($documentStorage->temporaryUrl($document->file_path));
    }

    private function scoped(ProcedureTask $task, ProcedureTaskDocument $document): ProcedureTaskDocument
    {
        abort_if($document->procedure_task_id !== $task->id, 404);

        return $document;
    }

    /**
     * 収集チェックのon/off切り替えに伴うcollected_at／retention_untilの整合を取る
     * （企画書7.7章：collected_at確定時にretention_yearsからretention_untilを自動計算）。
     *
     * @return array<string, mixed>
     */
    private function collectedAttributes(ProcedureTaskDocument $document, bool $collected): array
    {
        if ($collected && ! $document->is_collected) {
            $collectedAt = now();
            $retentionYears = $document->retention_years;

            return [
                'is_collected' => true,
                'collected_at' => $collectedAt,
                'retention_until' => $retentionYears ? $collectedAt->copy()->addYears($retentionYears)->toDateString() : null,
            ];
        }

        if (! $collected && $document->is_collected) {
            return [
                'is_collected' => false,
                'collected_at' => null,
                'retention_until' => null,
            ];
        }

        return ['is_collected' => $document->is_collected];
    }
}
