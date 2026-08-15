<?php

namespace App\Http\Controllers;

use App\Services\Import\ImportReportBuilder;
use App\Services\Import\ImportWizardService;
use App\Services\Import\ProcedureTaskImportProcessor;
use App\Services\Import\SpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 手続きタスクのExcelインポートウィザード（アップロード→マッピング→確認→確定、企画書6章）。
 * 実行はowner限定（企画書7章権限表、Gate::manage-imports）。
 */
class ProcedureTaskImportController extends Controller
{
    public function __construct(
        private readonly ImportWizardService $wizard,
        private readonly SpreadsheetReader $reader,
        private readonly ImportReportBuilder $reportBuilder,
        private readonly ProcedureTaskImportProcessor $processor,
    ) {}

    public function create(): Response
    {
        $this->authorize('manage-imports');

        return Inertia::render('Imports/Upload', [
            'title' => '手続きタスクのExcelインポート',
            'uploadRoute' => route('tasks.import.preview'),
            'backRoute' => route('tasks.index'),
        ]);
    }

    public function preview(Request $request): Response
    {
        $this->authorize('manage-imports');

        $validated = $request->validate([
            'file' => 'required|file|max:5120|mimes:xlsx,xls,csv',
        ]);

        $officeId = Auth::user()->office_id;
        $token = $this->wizard->storeTempFile($officeId, $validated['file']);
        $path = $this->wizard->resolvePath($officeId, $token);
        $preview = $this->reader->readHeaderAndPreview($path);

        return Inertia::render('Imports/Mapping', [
            'title' => '手続きタスクのExcelインポート',
            'token' => $token,
            'headers' => $preview['headers'],
            'previewRows' => $preview['rows'],
            'targetFields' => $this->processor->fields(),
            'validateRoute' => route('tasks.import.validate'),
            'backRoute' => route('tasks.import.create'),
        ]);
    }

    public function validateMapping(Request $request): Response
    {
        $this->authorize('manage-imports');

        $validated = $request->validate([
            'token' => 'required|uuid',
            'mapping' => 'required|array',
            'mapping.*' => 'nullable|string',
        ]);

        $office = Auth::user()->office;
        $path = $this->wizard->resolvePath($office->id, $validated['token']);
        $mapping = $this->reportBuilder->normalizeMapping($validated['mapping']);

        $report = $this->reportBuilder->build($this->processor, $path, $mapping, $office);

        return Inertia::render('Imports/Review', [
            'title' => '手続きタスクのExcelインポート',
            'token' => $validated['token'],
            'mapping' => $validated['mapping'],
            'summary' => $report['summary'],
            'rows' => $report['rows'],
            'commitRoute' => route('tasks.import.commit'),
            'backRoute' => route('tasks.import.create'),
        ]);
    }

    public function commit(Request $request): RedirectResponse
    {
        $this->authorize('manage-imports');

        $validated = $request->validate([
            'token' => 'required|uuid',
            'mapping' => 'required|array',
            'mapping.*' => 'nullable|string',
        ]);

        $office = Auth::user()->office;
        $path = $this->wizard->resolvePath($office->id, $validated['token']);
        $mapping = $this->reportBuilder->normalizeMapping($validated['mapping']);

        // reviewで返した判定を鵜呑みにせず、確定直前に再検証してから反映する（TOCTOU対策）。
        $report = $this->reportBuilder->build($this->processor, $path, $mapping, $office);

        $createdCount = 0;
        DB::transaction(function () use ($report, &$createdCount) {
            foreach ($report['rows'] as $row) {
                if ($row['errors'] === []) {
                    $this->processor->create($row['resolved']);
                    $createdCount++;
                }
            }
        });

        $this->wizard->deleteTempFile($office->id, $validated['token']);

        $skippedCount = count($report['rows']) - $createdCount;
        $message = "{$createdCount}件の手続きタスクを取り込みました。";
        if ($skippedCount > 0) {
            $message .= "（{$skippedCount}件はエラーのためスキップしました）";
        }

        return redirect()->route('tasks.index')->with('success', $message);
    }
}
