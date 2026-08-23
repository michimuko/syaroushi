<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\PlatformAdmin;
use App\Notifications\StripePaymentFailed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
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
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event),
            'invoice.paid' => $this->handleInvoicePaid($event),
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

    /**
     * 定期購読の決済失敗を運営者へ能動的に知らせる。stripe_subscription_statusは
     * 別途customer.subscription.updatedでpast_due等に追従するが、そちらは検知のための
     * 通知を出さないため、このイベントを運営者への一次アラートとして使う。
     */
    private function handleInvoicePaymentFailed(Event $event): void
    {
        $invoice = $event->data->object;

        $office = Office::where('stripe_customer_id', $invoice->customer)->first();

        if ($office === null) {
            return;
        }

        $office->update(['stripe_payment_failed_at' => now()]);

        $admins = PlatformAdmin::all();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new StripePaymentFailed(
                office: $office,
                amountDue: $invoice->amount_due ?? null,
                hostedInvoiceUrl: $invoice->hosted_invoice_url ?? null,
            ));
        }
    }

    /**
     * 支払いが成功（再試行での回収を含む）したら「支払いエラー」表示を解消する。
     */
    private function handleInvoicePaid(Event $event): void
    {
        $invoice = $event->data->object;

        Office::where('stripe_customer_id', $invoice->customer)
            ->whereNotNull('stripe_payment_failed_at')
            ->first()
            ?->update(['stripe_payment_failed_at' => null]);
    }
}
