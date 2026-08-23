<?php

namespace App\Http\Controllers;

use App\Services\Import\ClientImportProcessor;
use App\Services\Import\ImportReportBuilder;
use App\Services\Import\ImportWizardService;
use App\Services\Import\SpreadsheetReader;
use App\Services\Import\SpreadsheetWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            'templateRoute' => route('clients.import.template'),
        ]);
    }

    /**
     * インポート用のExcelテンプレート（見出し＋入力例1行）をダウンロードする。
     * 見出しはfields()と同じ文言にすることで、次のマッピング画面で列名がそのまま参考になるようにする。
     */
    public function template(SpreadsheetWriter $writer): StreamedResponse
    {
        $this->authorize('manage-imports');

        $office = Auth::user()->office;
        $fields = $this->processor->fields($office);

        $headers = array_map(fn (array $field) => $field['label'], $fields);

        $exampleByKey = [
            'name' => 'サンプル商事株式会社',
            'representative_name' => '山田 太郎',
            'address' => '東京都千代田区1-1-1',
            'phone' => '03-1234-5678',
            'email' => 'sample@example.com',
            'contract_start_date' => '2026-04-01',
            'status' => '契約中',
            'assigned_user_name' => '（担当スタッフの氏名を入力）',
            'notes' => '（自由記載。空欄可）',
        ];
        $example = array_map(fn (array $field) => $exampleByKey[$field['key']] ?? '（自由記載。空欄可）', $fields);

        return $writer->export('xlsx', '顧問先インポートテンプレート', $headers, [$example]);
    }

    public function preview(Request $request): Response
    {
        $this->authorize('manage-imports');

        $validated = $request->validate([
            // mimesは内容から推測したMIMEタイプで判定するため、タブ区切りの内容を
            // .csvで保存したファイル等がtext/plain判定となり弾かれてしまう。
            // 拡張子ベースのextensionsを使い、区切り文字の判定はSpreadsheetReaderに委ねる。
            'file' => 'required|file|max:5120|extensions:xlsx,xls,csv,tsv',
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
