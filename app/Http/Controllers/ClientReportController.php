<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientReport;
use App\Services\ClientReportPdfGenerator;
use App\Services\ClientReportStorage;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 顧問先向け進捗レポート（企画書5-B）の生成・履歴管理。
 */
class ClientReportController extends Controller
{
    public function index(Client $client): Response
    {
        $this->authorize('viewAny', [ClientReport::class, $client]);

        return Inertia::render('Clients/Reports/Index', [
            'client' => $client->only('id', 'name'),
            'reports' => $client->reports()
                ->with('generatedBy:id,name')
                ->latest('created_at')
                ->get()
                ->map(fn (ClientReport $report) => [
                    'id' => $report->id,
                    'period_start' => $report->period_start->toDateString(),
                    'period_end' => $report->period_end->toDateString(),
                    'generated_by_name' => $report->generatedBy->name,
                    'created_at' => $report->created_at->toDateTimeString(),
                ]),
        ]);
    }

    public function store(Request $request, Client $client, ClientReportPdfGenerator $generator, ClientReportStorage $storage): RedirectResponse
    {
        $this->authorize('create', [ClientReport::class, $client]);

        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'comment' => 'nullable|string|max:2000',
        ]);

        $periodStart = $validated['period_start'];
        $periodEnd = $validated['period_end'];

        $pdfContents = $generator->generate(
            $client,
            CarbonImmutable::parse($periodStart),
            CarbonImmutable::parse($periodEnd),
            $validated['comment'] ?? null,
            Auth::user(),
        );

        $path = $storage->store($client->office_id, $pdfContents);

        $client->reports()->create([
            'generated_by' => Auth::id(),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'comment' => $validated['comment'] ?? null,
            'pdf_path' => $path,
            'created_at' => now(),
        ]);

        return back()->with('success', '進捗レポートを生成しました。');
    }

    public function download(Client $client, ClientReport $report, ClientReportStorage $storage): RedirectResponse
    {
        abort_if($report->client_id !== $client->id, 404);
        $this->authorize('view', $report);

        return redirect()->away($storage->temporaryUrl($report->pdf_path));
    }
}
