<?php

namespace App\Http\Controllers;

use App\Models\OfficeInvoice;
use App\Services\Stripe\StripeCheckoutStarter;
use App\Services\Stripe\StripeSubscriptionGateway;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * 契約プランの実際の決済（Stripe Checkout / Customer Portal）。
 * 閲覧専用のBillingControllerとは別に、決済処理という副作用を持つアクションとして分離する。
 * 認可はBillingControllerと同じくOfficeInvoicePolicy（owner限定）を流用する。
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private readonly StripeSubscriptionGateway $gateway,
        private readonly StripeCheckoutStarter $checkoutStarter,
    ) {}

    public function checkout(): Response
    {
        $this->authorize('viewAny', OfficeInvoice::class);

        $office = Auth::user()->office->loadMissing('billingPlan');
        $plan = $office->billingPlan;

        if ($office->hasActiveStripeSubscription()) {
            return back()->with('error', 'すでにお申し込み済みです。プランの変更・解約はお支払い方法の管理画面から行ってください。');
        }

        if ($plan === null || $plan->stripe_price_id === null) {
            return back()->with('error', 'ご契約プランに決済用の設定がありません。運営者までお問い合わせください。');
        }

        $checkoutUrl = $this->checkoutStarter->start(
            office: $office,
            ownerEmail: Auth::user()->email,
            successUrl: route('settings.billing.index').'?checkout=success',
            cancelUrl: route('settings.billing.index').'?checkout=cancel',
        );

        // Inertiaのfetch/XHR経由のリクエストでは通常のredirect()だとブラウザ遷移が起きないため、
        // 外部URL（Stripeのホスト型Checkoutページ）へはInertia::locationで完全な画面遷移をさせる。
        return Inertia::location($checkoutUrl);
    }

    public function portal(): Response
    {
        $this->authorize('viewAny', OfficeInvoice::class);

        $office = Auth::user()->office;

        if ($office->stripe_customer_id === null) {
            return back()->with('error', 'まだ決済が開始されていません。先にプランへのお申し込みを行ってください。');
        }

        $portalUrl = $this->gateway->createPortalSessionUrl(
            customerId: $office->stripe_customer_id,
            returnUrl: route('settings.billing.index'),
        );

        return Inertia::location($portalUrl);
    }
}
