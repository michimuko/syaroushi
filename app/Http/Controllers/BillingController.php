<?php

namespace App\Http\Controllers;

use App\Models\BillingSetting;
use App\Models\OfficeInvoice;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 事務所の請求状況の閲覧画面（顧問先数×ユーザー数の階層プラン制）。
 * 現在の契約プラン・利用状況・見込み金額・過去の請求記録に加え、Stripe Checkout/Customer Portal
 * への導線（SubscriptionController）もこの画面から提供する。
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
                'has_active_stripe_subscription' => $office->hasActiveStripeSubscription(),
                'stripe_subscription_status' => $office->stripe_subscription_status,
                'has_stripe_customer' => $office->stripe_customer_id !== null,
                'is_past_trial_without_subscription' => $office->isPastTrialWithoutSubscription(),
                'is_in_deletion_warning_period' => $office->isInDeletionWarningPeriod(),
                'scheduled_deletion_at' => $office->scheduledDeletionAt()?->toDateString(),
            ],
            'billingPlan' => $office->billingPlan ? [
                'name' => $office->billingPlan->name,
                'max_clients' => $office->billingPlan->max_clients,
                'max_users' => $office->billingPlan->max_users,
                'checkout_available' => $office->billingPlan->stripe_price_id !== null,
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
