<?php

namespace App\Services;

use App\Contracts\CheckoutSessionGateway;
use App\Models\Payment;
use App\Models\Shipment;
use RuntimeException;
use Stripe\StripeClient;

class StripeCheckoutSessionGateway implements CheckoutSessionGateway
{
    public function create(Payment $payment, Shipment $shipment): array
    {
        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($payment->currency),
                    'unit_amount' => (int) round((float) $payment->amount * 100),
                    'product_data' => [
                        'name' => __('app.payments.shipment_label', ['tracking' => $shipment->tracking_number]),
                    ],
                ],
            ]],
            'client_reference_id' => (string) $payment->id,
            'customer_email' => $payment->user->email,
            'metadata' => [
                'payment_id' => (string) $payment->id,
                'shipment_id' => (string) $shipment->id,
                'customer_id' => (string) $payment->user_id,
            ],
            'success_url' => route('payments.result') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payments.result', [
                'cancelled' => 1,
                'payment_id' => $payment->id,
            ]),
        ]);

        if (! is_string($session->url) || $session->url === '') {
            throw new RuntimeException('Stripe did not return a hosted Checkout URL.');
        }

        return ['id' => $session->id, 'url' => $session->url];
    }

    public function retrieve(string $sessionId): array
    {
        return $this->client()->checkout->sessions->retrieve($sessionId)->toArray();
    }

    public function expire(string $sessionId): array
    {
        return $this->client()->checkout->sessions->expire($sessionId)->toArray();
    }

    private function client(): StripeClient
    {
        $secret = (string) config('services.stripe.secret');

        if (! str_starts_with($secret, 'sk_test_')) {
            throw new RuntimeException('Stripe Checkout is configured for test mode only.');
        }

        return new StripeClient($secret);
    }
}