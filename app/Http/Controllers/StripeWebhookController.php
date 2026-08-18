<?php

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Http\Request;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

/**
 * StripeからのWebhook受信口。認証・CSRFの対象外にする必要があるため
 * bootstrap/app.phpでCSRF検証除外、routes/web.phpではauth:webグループの外側に置く。
 */
class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $secret = config('services.stripe.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            return response('webhook secret not configured', 400);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                $secret,
            );
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response('invalid payload or signature', 400);
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event),
            'customer.subscription.created', 'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
            default => null,
        };

        return response('ok', 200);
    }

    private function handleCheckoutCompleted(Event $event): void
    {
        $session = $event->data->object;

        if (($session->mode ?? null) !== 'subscription' || empty($session->customer)) {
            return;
        }

        Office::where('stripe_customer_id', $session->customer)->first()?->update([
            'stripe_subscription_id' => $session->subscription,
            'stripe_subscription_status' => 'active',
        ]);
    }

    private function handleSubscriptionUpdated(Event $event): void
    {
        $subscription = $event->data->object;

        Office::where('stripe_customer_id', $subscription->customer)->first()?->update([
            'stripe_subscription_id' => $subscription->id,
            'stripe_subscription_status' => $subscription->status,
        ]);
    }

    private function handleSubscriptionDeleted(Event $event): void
    {
        $subscription = $event->data->object;

        Office::where('stripe_customer_id', $subscription->customer)->first()?->update([
            'stripe_subscription_status' => 'canceled',
        ]);
    }
}
