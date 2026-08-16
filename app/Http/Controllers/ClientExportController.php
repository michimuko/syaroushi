<?php

namespace App\Http\Controllers;

use App\Enums\CustomFieldTarget;
use App\Models\Client;
use App\Models\CustomFieldDefinition;
use App\Services\CustomFieldSpreadsheetFormatter;
use App\Services\Import\SpreadsheetWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 顧問先一覧をExcel(xlsx)/CSVへエクスポートする（Excel移行アシスタント、企画書6章の「ロックインの恐怖を取り除く」方針）。
 * カスタムフィールド（企画書5-E）が定義されている場合は、その値を末尾列として追加出力する。
 */
class ClientExportController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const HEADERS = ['顧問先名', '代表者', '住所', '電話番号', 'メールアドレス', '契約開始日', 'ステータス', '担当者', 'メモ'];

    public function index(Request $request, SpreadsheetWriter $writer, CustomFieldSpreadsheetFormatter $formatter): StreamedResponse
    {
        $this->authorize('viewAny', Client::class);

        $validated = $request->validate([
            'format' => 'nullable|in:xlsx,csv',
        ]);

        $office = Auth::user()->office;
        $definitions = CustomFieldDefinition::query()
            ->where('office_id', $office->id)
            ->where('target', CustomFieldTarget::Client)
            ->orderBy('id')
            ->get();

        $headers = [...self::HEADERS, ...$definitions->pluck('label')->all()];

        $rows = Client::query()
            ->with('assignedUser:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Client $client) => [
                $client->name,
                $client->representative_name,
                $client->address,
                $client->phone,
                $client->email,
                $client->contract_start_date?->toDateString(),
                $client->status->label(),
                $client->assignedUser?->name,
                $client->notes,
                ...$definitions->map(fn (CustomFieldDefinition $definition) => $formatter->formatForExport(
                    $definition->field_type,
                    $client->custom_fields[$definition->id] ?? null,
                ))->all(),
            ]);

        return $writer->export($validated['format'] ?? 'xlsx', '顧問先一覧', $headers, $rows);
    }
}
