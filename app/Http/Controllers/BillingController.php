<?php

namespace App\Http\Controllers;

use App\Models\BillingSetting;
use App\Models\OfficeInvoice;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 事務所の請求状況の閲覧画面（顧問先数×ユーザー数の階層プラン制）。
 * 決済連携はなく、現在の契約プラン・利用状況・見込み金額・過去の請求記録を表示するのみ。
 * owner限定（UserPolicy等と同様、事務所全体に影響する機微な情報のため委譲対象外）。
 */
class BillingController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', OfficeInvoice::class);

        $office = Auth::user()->office->loadMissing('billingPlan');
        $setting = BillingSetting::current();

        return Inertia::render('Settings/Billing/Index', [
            'office' => [
                'is_trial_active' => $office->isTrialActive(),
                'trial_ends_at' => $office->trial_ends_at?->toDateString(),
            ],
            'billingPlan' => $office->billingPlan ? [
                'name' => $office->billingPlan->name,
                'max_clients' => $office->billingPlan->max_clients,
                'max_users' => $office->billingPlan->max_users,
            ] : null,
            'billingCycleLabel' => $setting->billing_cycle->label(),
            'currentClientCount' => $office->billableClientCount(),
            'currentUserCount' => $office->currentUserCount(),
            'estimatedMonthlyAmount' => $office->custom_monthly_price ?? $office->billingPlan?->monthly_price,
            'exceededLimits' => $office->exceedsPlanLimits(),
            'invoices' => $office->invoices()->orderByDesc('period_start')->paginate(12),
        ]);
    }
}
