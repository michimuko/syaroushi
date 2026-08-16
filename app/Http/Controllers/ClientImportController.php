<?php

namespace App\Http\Controllers;

use App\Services\Import\ClientImportProcessor;
use App\Services\Import\ImportReportBuilder;
use App\Services\Import\ImportWizardService;
use App\Services\Import\SpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 顧問先のExcelインポートウィザード（アップロード→マッピング→確認→確定、企画書6章）。
 * 実行はowner限定（企画書7章権限表、Gate::manage-imports）。
 */
class ClientImportController extends Controller
{
    public function __construct(
        private readonly ImportWizardService $wizard,
        private readonly SpreadsheetReader $reader,
        private readonly ImportReportBuilder $reportBuilder,
        private readonly ClientImportProcessor $processor,
    ) {}

    public function create(): Response
    {
        $this->authorize('manage-imports');

        return Inertia::render('Imports/Upload', [
            'title' => '顧問先のExcelインポート',
            'uploadRoute' => route('clients.import.preview'),
            'backRoute' => route('clients.index'),
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
            'title' => '顧問先のExcelインポート',
            'token' => $token,
            'headers' => $preview['headers'],
            'previewRows' => $preview['rows'],
            'targetFields' => $this->processor->fields(Auth::user()->office),
            'validateRoute' => route('clients.import.validate'),
            'backRoute' => route('clients.import.create'),
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
            'title' => '顧問先のExcelインポート',
            'token' => $validated['token'],
            'mapping' => $validated['mapping'],
            'summary' => $report['summary'],
            'rows' => $report['rows'],
            'commitRoute' => route('clients.import.commit'),
            'backRoute' => route('clients.import.create'),
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
        $message = "{$createdCount}件の顧問先を取り込みました。";
        if ($skippedCount > 0) {
            $message .= "（{$skippedCount}件はエラーのためスキップしました）";
        }

        return redirect()->route('clients.index')->with('success', $message);
    }
}
