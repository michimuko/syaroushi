<?php

use App\Models\Office;
use App\Models\PlatformAdmin;
use App\Notifications\StripePaymentFailed;
use Illuminate\Support\Facades\Notification;
use Stripe\WebhookSignature;

beforeEach(function () {
    config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
});

function signedStripeWebhookRequest(array $payload): array
{
    $body = json_encode($payload);
    $signature = WebhookSignature::generateSignatureHeader($body, config('services.stripe.webhook_secret'));

    return [$body, $signature];
}

test('checkout.session.completed activates the subscription for the matching office', function () {
    $office = Office::factory()->create(['stripe_customer_id' => 'cus_abc', 'stripe_subscription_id' => null, 'stripe_subscription_status' => null]);

    [$body, $signature] = signedStripeWebhookRequest([
        'id' => 'evt_1',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => 'cs_1',
                'object' => 'checkout.session',
                'mode' => 'subscription',
                'customer' => 'cus_abc',
                'subscription' => 'sub_1',
            ],
        ],
    ]);

    $response = $this->call('POST', route('webhooks.stripe'), [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response->assertOk();
    expect($office->refresh())
        ->stripe_subscription_id->toBe('sub_1')
        ->stripe_subscription_status->toBe('active');
});

test('customer.subscription.updated syncs the subscription status', function () {
    $office = Office::factory()->create([
        'stripe_customer_id' => 'cus_abc',
        'stripe_subscription_id' => 'sub_1',
        'stripe_subscription_status' => 'active',
    ]);

    [$body, $signature] = signedStripeWebhookRequest([
        'id' => 'evt_2',
        'object' => 'event',
        'type' => 'customer.subscription.updated',
        'data' => [
            'object' => [
                'id' => 'sub_1',
                'object' => 'subscription',
                'customer' => 'cus_abc',
                'status' => 'past_due',
            ],
        ],
    ]);

    $response = $this->call('POST', route('webhooks.stripe'), [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response->assertOk();
    expect($office->refresh()->stripe_subscription_status)->toBe('past_due');
});

test('customer.subscription.deleted marks the office subscription as canceled', function () {
    $office = Office::factory()->create([
        'stripe_customer_id' => 'cus_abc',
        'stripe_subscription_id' => 'sub_1',
        'stripe_subscription_status' => 'active',
    ]);

    [$body, $signature] = signedStripeWebhookRequest([
        'id' => 'evt_3',
        'object' => 'event',
        'type' => 'customer.subscription.deleted',
        'data' => [
            'object' => [
                'id' => 'sub_1',
                'object' => 'subscription',
                'customer' => 'cus_abc',
                'status' => 'canceled',
            ],
        ],
    ]);

    $response = $this->call('POST', route('webhooks.stripe'), [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response->assertOk();
    expect($office->refresh()->stripe_subscription_status)->toBe('canceled');
});

test('invoice.payment_failed flags the office and notifies every platform admin', function () {
    Notification::fake();

    $office = Office::factory()->create([
        'stripe_customer_id' => 'cus_abc',
        'stripe_subscription_id' => 'sub_1',
        'stripe_subscription_status' => 'active',
        'stripe_payment_failed_at' => null,
    ]);
    $adminA = PlatformAdmin::factory()->create();
    $adminB = PlatformAdmin::factory()->create();

    [$body, $signature] = signedStripeWebhookRequest([
        'id' => 'evt_6',
        'object' => 'event',
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'id' => 'in_1',
                'object' => 'invoice',
                'customer' => 'cus_abc',
                'amount_due' => 6800,
                'hosted_invoice_url' => 'https://invoice.stripe.com/i/in_1',
            ],
        ],
    ]);

    $response = $this->call('POST', route('webhooks.stripe'), [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response->assertOk();
    expect($office->refresh()->stripe_payment_failed_at)->not->toBeNull();

    Notification::assertSentTo([$adminA, $adminB], StripePaymentFailed::class, function ($notification) use ($office) {
        return $notification->office->is($office)
            && $notification->amountDue === 6800
            && $notification->hostedInvoiceUrl === 'https://invoice.stripe.com/i/in_1';
    });
});

test('invoice.payment_failed for an unknown customer does not error and sends no notification', function () {
    Notification::fake();
    PlatformAdmin::factory()->create();

    [$body, $signature] = signedStripeWebhookRequest([
        'id' => 'evt_7',
        'object' => 'event',
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'id' => 'in_2',
                'object' => 'invoice',
                'customer' => 'cus_unknown',
                'amount_due' => 1000,
            ],
        ],
    ]);

    $response = $this->call('POST', route('webhooks.stripe'), [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response->assertOk();
    Notification::assertNothingSent();
});

test('invoice.paid clears a previously flagged payment issue', function () {
    $office = Office::factory()->create([
        'stripe_customer_id' => 'cus_abc',
        'stripe_subscription_id' => 'sub_1',
        'stripe_subscription_status' => 'active',
        'stripe_payment_failed_at' => now(),
    ]);

    [$body, $signature] = signedStripeWebhookRequest([
        'id' => 'evt_8',
        'object' => 'event',
        'type' => 'invoice.paid',
        'data' => [
            'object' => [
                'id' => 'in_3',
                'object' => 'invoice',
                'customer' => 'cus_abc',
                'amount_due' => 6800,
            ],
        ],
    ]);

    $response = $this->call('POST', route('webhooks.stripe'), [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response->assertOk();
    expect($office->refresh()->stripe_payment_failed_at)->toBeNull();
});

test('invoice.paid is a no-op when the office had no payment issue flagged', function () {
    $office = Office::factory()->create([
        'stripe_customer_id' => 'cus_abc',
        'stripe_subscription_id' => 'sub_1',
        'stripe_subscription_status' => 'active',
        'stripe_payment_failed_at' => null,
    ]);

    [$body, $signature] = signedStripeWebhookRequest([
        'id' => 'evt_9',
        'object' => 'event',
        'type' => 'invoice.paid',
        'data' => [
            'object' => [
                'id' => 'in_4',
                'object' => 'invoice',
                'customer' => 'cus_abc',
                'amount_due' => 6800,
            ],
        ],
    ]);

    $response = $this->call('POST', route('webhooks.stripe'), [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response->assertOk();
    expect($office->refresh()->stripe_payment_failed_at)->toBeNull();
});

test('a request with an invalid signature is rejected', function () {
    $body = json_encode(['id' => 'evt_4', 'type' => 'checkout.session.completed']);

    $response = $this->call('POST', route('webhooks.stripe'), [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 't=1,v1=invalid',
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response->assertStatus(400);
});

test('the webhook endpoint is exempt from CSRF verification', function () {
    [$body, $signature] = signedStripeWebhookRequest([
        'id' => 'evt_5',
        'object' => 'event',
        'type' => 'some.unhandled.event',
        'data' => ['object' => []],
    ]);

    $response = $this->call('POST', route('webhooks.stripe'), [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response->assertOk();
});
