<?php

namespace App\Http\Controllers;

use App\Models\BillingSetting;
use App\Models\OfficeInvoice;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 事務所の請求状況の閲覧画面（企画書11章：顧問先数に応じた従量制）。
 * 決済連携はなく、現在の課金対象顧問先数・見込み金額・過去の請求記録を表示するのみ。
 * owner限定（UserPolicy等と同様、事務所全体に影響する機微な情報のため委譲対象外）。
 */
class BillingController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', OfficeInvoice::class);

        $office = Auth::user()->office;
        $setting = BillingSetting::current();
        $clientCount = $office->billableClientCount();

        return Inertia::render('Settings/Billing/Index', [
            'office' => [
                'contract_plan' => $office->contract_plan,
                'is_trial_active' => $office->isTrialActive(),
                'trial_ends_at' => $office->trial_ends_at?->toDateString(),
            ],
            'billingSetting' => [
                'unit_price_per_client' => $setting->unit_price_per_client,
                'billing_cycle' => $setting->billing_cycle->label(),
            ],
            'currentClientCount' => $clientCount,
            'estimatedMonthlyAmount' => $clientCount * $setting->unit_price_per_client,
            'invoices' => $office->invoices()->orderByDesc('period_start')->paginate(12),
        ]);
    }
}
