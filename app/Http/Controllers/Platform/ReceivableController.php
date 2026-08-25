<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\OfficeInvoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 未収金（AR）管理。billing:generate-invoicesバッチが生成するDB請求記録
 * （一度もStripe決済連携をしていない事務所向け）のうち、入金が未確認のものを
 * 運営者が横断的に把握・確認できるようにする。Stripe決済連携済みの事務所の
 * 決済状況はStripe側が正のためここでは扱わない（office_invoicesも生成されない）。
 */
class ReceivableController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'status' => 'nullable|in:unpaid,paid,all',
            'search' => 'nullable|string|max:255',
        ]);
        $status = $validated['status'] ?? 'unpaid';

        $invoices = OfficeInvoice::query()
            ->with('office:id,name,office_code')
            ->when($status === 'unpaid', fn ($query) => $query->whereNull('paid_at'))
            ->when($status === 'paid', fn ($query) => $query->whereNotNull('paid_at'))
            ->when(
                $validated['search'] ?? null,
                fn ($query, $search) => $query->whereHas(
                    'office',
                    fn ($q) => $q->where('name', 'like', "%{$search}%")
                ),
            )
            ->orderByDesc('period_start')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Receivables/Index', [
            'invoices' => $invoices,
            'filters' => [
                'status' => $status,
                'search' => $validated['search'] ?? '',
            ],
            'summary' => [
                'unpaidTotal' => (int) OfficeInvoice::whereNull('paid_at')->sum('amount'),
                'unpaidCount' => OfficeInvoice::whereNull('paid_at')->count(),
            ],
        ]);
    }

    public function markPaid(OfficeInvoice $officeInvoice): RedirectResponse
    {
        if ($officeInvoice->paid_at === null) {
            $officeInvoice->update(['paid_at' => now()]);
        }

        return back()
            ->with('success', '入金を確認しました。')
            ->with('highlightId', $officeInvoice->id);
    }
}
