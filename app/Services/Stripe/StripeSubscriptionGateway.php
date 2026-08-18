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
}
