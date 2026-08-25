<?php

namespace App\Services\Stripe;

use App\Models\Office;

/**
 * Stripe顧客の確保・トライアル終了日からのtrial_end算出・Checkout Session URL生成をまとめる。
 * SubscriptionController（オーナーの能動的な申込み）とRegisteredUserController（セルフ登録直後の
 * 自動遷移）の両方から呼ばれる共通ロジック。「申込み可能かどうか」の業務判断（プラン未設定・
 * 既に契約済み等）は呼び出し側の責務のまま残し、ここでは副作用のある手続きのみを行う。
 * 呼び出し前提として$office->billingPlan?->stripe_price_idが非nullであることを呼び出し側が保証する。
 */
class StripeCheckoutStarter
{
    public function __construct(private readonly StripeSubscriptionGateway $gateway) {}

    public function start(Office $office, string $ownerEmail, string $successUrl, string $cancelUrl): string
    {
        $office->loadMissing('billingPlan');

        if ($office->stripe_customer_id === null) {
            $customerId = $this->gateway->createCustomer(
                email: $ownerEmail,
                name: $office->name,
                metadata: ['office_id' => (string) $office->id],
            );

            $office->update(['stripe_customer_id' => $customerId]);
        }

        $trialEnd = $office->isTrialActive()
            ? $office->trial_ends_at->copy()->endOfDay()->timestamp
            : null;

        return $this->gateway->createCheckoutSessionUrl(
            customerId: $office->stripe_customer_id,
            priceId: $office->billingPlan->stripe_price_id,
            subscriptionMetadata: ['office_id' => (string) $office->id],
            successUrl: $successUrl,
            cancelUrl: $cancelUrl,
            trialEnd: $trialEnd,
        );
    }
}
