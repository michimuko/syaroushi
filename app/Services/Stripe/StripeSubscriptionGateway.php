<?php

namespace App\Services\Stripe;

use Stripe\StripeClient;

/**
 * SubscriptionControllerからStripe SDKへの直接依存を切り離すための薄いラッパー。
 * StripeClientのマジックメソッド（$stripe->customers->create()等）はMockeryで直接
 * モックしづらいため、テストではこのクラス自体を差し替える。
 */
class StripeSubscriptionGateway
{
    public function __construct(private readonly StripeClient $stripe) {}

    /**
     * @param  array<string, string>  $metadata
     */
    public function createCustomer(string $email, string $name, array $metadata): string
    {
        return $this->stripe->customers->create([
            'email' => $email,
            'name' => $name,
            'metadata' => $metadata,
        ])->id;
    }

    /**
     * @param  array<string, string>  $subscriptionMetadata
     */
    public function createCheckoutSessionUrl(
        string $customerId,
        string $priceId,
        array $subscriptionMetadata,
        string $successUrl,
        string $cancelUrl,
    ): string {
        return $this->stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'line_items' => [['price' => $priceId, 'quantity' => 1]],
            'subscription_data' => ['metadata' => $subscriptionMetadata],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ])->url;
    }

    public function createPortalSessionUrl(string $customerId, string $returnUrl): string
    {
        return $this->stripe->billingPortal->sessions->create([
            'customer' => $customerId,
            'return_url' => $returnUrl,
        ])->url;
    }

    /**
     * 既存のPrice（プラン標準価格）が紐づくProductのIDを取得する。
     * 個別値引き額(custom_monthly_price)用の動的Priceを作る際、プランと同じProductの下に
     * 作成することで、Stripe側にProductが乱立しないようにするために使う。
     */
    public function productIdForPrice(string $priceId): ?string
    {
        $product = $this->stripe->prices->retrieve($priceId)->product;

        return is_string($product) ? $product : ($product->id ?? null);
    }

    /**
     * サブスクリプションの価格を同期し、差額をその場でプロレーション請求する
     * （運営管理画面の「請求確定」ボタン用）。
     * $priceId（プラン標準のStripe Price）が指定されればそれをそのまま使う。
     * 指定が無ければ$unitAmountYenを金額とする動的Price（price_data）をその場で作り、
     * $productIdがあればそのProductの下に、無ければ新規Productとして作成する。
     *
     * proration_behavior=always_invoiceにより、価格変更の差額を待たずにその場で
     * 請求書を確定・即時決済（保存済み支払い方法への自動課金）まで行う。
     *
     * @return array{invoice_id: ?string, invoice_status: ?string, amount_paid: ?int}
     */
    public function syncSubscriptionPrice(
        string $subscriptionId,
        ?string $priceId,
        ?int $unitAmountYen,
        ?string $productId,
        string $productFallbackName,
    ): array {
        $subscription = $this->stripe->subscriptions->retrieve($subscriptionId);
        $itemId = $subscription->items->data[0]->id;

        $item = ['id' => $itemId];

        if ($priceId !== null) {
            $item['price'] = $priceId;
        } else {
            $priceData = [
                'currency' => 'jpy',
                'unit_amount' => $unitAmountYen,
                'recurring' => ['interval' => 'month'],
            ];
            if ($productId !== null) {
                $priceData['product'] = $productId;
            } else {
                $priceData['product_data'] = ['name' => $productFallbackName];
            }
            $item['price_data'] = $priceData;
        }

        $updated = $this->stripe->subscriptions->update($subscriptionId, [
            'items' => [$item],
            'proration_behavior' => 'always_invoice',
            'expand' => ['latest_invoice'],
        ]);

        $invoice = $updated->latest_invoice;

        return [
            'invoice_id' => $invoice->id ?? null,
            'invoice_status' => $invoice->status ?? null,
            'amount_paid' => $invoice->amount_paid ?? null,
        ];
    }
}
