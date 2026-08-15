<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\Import\SpreadsheetWriter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 顧問先一覧をExcel(xlsx)/CSVへエクスポートする（Excel移行アシスタント、企画書6章の「ロックインの恐怖を取り除く」方針）。
 */
class ClientExportController extends Controller
{
    /**
     * @var array<int, string>
     */
    private const HEADERS = ['顧問先名', '代表者', '住所', '電話番号', 'メールアドレス', '契約開始日', 'ステータス', '担当者', 'メモ'];

    public function index(Request $request, SpreadsheetWriter $writer): StreamedResponse
    {
        $this->authorize('viewAny', Client::class);

        $validated = $request->validate([
            'format' => 'nullable|in:xlsx,csv',
        ]);

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
            ]);

        return $writer->export($validated['format'] ?? 'xlsx', '顧問先一覧', self::HEADERS, $rows);
    }
}
