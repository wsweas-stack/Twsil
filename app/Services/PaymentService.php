<?php

namespace App\Services;

use App\Contracts\CheckoutSessionGateway;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use App\Notifications\OperationalNotification;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

class PaymentService
{
    public function __construct(private readonly CheckoutSessionGateway $checkoutGateway) {}

    public function createCheckout(User $customer, Shipment $shipment): string
    {
        abort_unless($shipment->customer_id === $customer->id, 404);
        abort_unless(
            $shipment->status === ShipmentStatus::AwaitingPayment
                && (float) $shipment->delivery_fee > 0,
            409,
        );

        $payment = DB::transaction(function () use ($customer, $shipment): Payment {
            $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            abort_unless($lockedShipment->customer_id === $customer->id, 404);
            abort_unless($lockedShipment->status === ShipmentStatus::AwaitingPayment, 409);

            $payment = Payment::query()->where('shipment_id', $lockedShipment->id)->lockForUpdate()->first();

            if ($payment?->status === PaymentStatus::Succeeded) {
                abort(409);
            }

            if ($payment === null) {
                $payment = Payment::query()->create([
                    'shipment_id' => $lockedShipment->id,
                    'user_id' => $customer->id,
                    'amount' => $lockedShipment->delivery_fee,
                    'currency' => $lockedShipment->currency,
                    'status' => PaymentStatus::Pending,
                ]);
            } else {
                $payment->forceFill([
                    'user_id' => $customer->id,
                    'amount' => $lockedShipment->delivery_fee,
                    'currency' => $lockedShipment->currency,
                    'status' => PaymentStatus::Pending,
                    'stripe_session_id' => null,
                    'stripe_payment_intent_id' => null,
                    'paid_at' => null,
                ])->save();
            }

            return $payment->load('user');
        });

        $session = $this->checkoutGateway->create($payment, $shipment);
        $payment->forceFill(['stripe_session_id' => $session['id']])->save();

        return $session['url'];
    }

    public function confirmCheckout(User $customer, Payment $payment): PaymentStatus
    {
        abort_unless($payment->user_id === $customer->id, 404);

        if ($payment->status === PaymentStatus::Succeeded) {
            return PaymentStatus::Succeeded;
        }

        abort_unless($payment->status === PaymentStatus::Pending && filled($payment->stripe_session_id), 409);

        $session = $this->checkoutGateway->retrieve($payment->stripe_session_id);
        $this->assertSessionMatchesPayment($payment, $session);

        if (($session['status'] ?? null) !== 'complete' || ($session['payment_status'] ?? null) !== 'paid') {
            return PaymentStatus::Pending;
        }

        return $this->recordSuccessfulPayment($customer, $payment, $session);
    }

    public function cancelCheckout(User $customer, Payment $payment): PaymentStatus
    {
        abort_unless($payment->user_id === $customer->id, 404);

        if ($payment->status === PaymentStatus::Succeeded) {
            return PaymentStatus::Succeeded;
        }

        if ($payment->status === PaymentStatus::Cancelled) {
            return PaymentStatus::Cancelled;
        }

        abort_unless($payment->status === PaymentStatus::Pending && filled($payment->stripe_session_id), 409);

        $session = $this->checkoutGateway->retrieve($payment->stripe_session_id);
        $this->assertSessionMatchesPayment($payment, $session);

        if (($session['status'] ?? null) === 'complete' && ($session['payment_status'] ?? null) === 'paid') {
            return $this->recordSuccessfulPayment($customer, $payment, $session);
        }

        if (($session['status'] ?? null) === 'open') {
            $session = $this->checkoutGateway->expire($payment->stripe_session_id);
            $this->assertSessionMatchesPayment($payment, $session);
        }

        if (($session['status'] ?? null) !== 'expired'
            || ($session['payment_status'] ?? null) === 'paid') {
            return PaymentStatus::Pending;
        }

        return DB::transaction(function () use ($customer, $payment): PaymentStatus {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($lockedPayment->status === PaymentStatus::Succeeded) {
                return PaymentStatus::Succeeded;
            }

            abort_unless(
                $lockedPayment->status === PaymentStatus::Pending
                    && $lockedPayment->stripe_session_id === $payment->stripe_session_id,
                409,
            );

            $shipment = Shipment::query()->lockForUpdate()->findOrFail($lockedPayment->shipment_id);
            abort_unless($shipment->status === ShipmentStatus::AwaitingPayment, 409);

            $lockedPayment->forceFill(['status' => PaymentStatus::Cancelled])->save();

            ShipmentEvent::query()->create([
                'shipment_id' => $shipment->id,
                'actor_id' => $customer->id,
                'status' => ShipmentStatus::AwaitingPayment->value,
                'type' => 'payment_cancelled',
                'description' => 'Checkout session expired at customer request',
                'occurred_at' => now(),
            ]);

            return PaymentStatus::Cancelled;
        });
    }

    /** @param array<string, mixed> $session */
    private function recordSuccessfulPayment(User $customer, Payment $payment, array $session): PaymentStatus
    {
        $this->assertSessionMatchesPayment($payment, $session);

        $confirmedPayment = DB::transaction(function () use ($customer, $payment, $session): Payment {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $shipment = Shipment::query()->lockForUpdate()->findOrFail($lockedPayment->shipment_id);

            if ($lockedPayment->status === PaymentStatus::Succeeded
                && $shipment->status === ShipmentStatus::Paid) {
                return $lockedPayment->load('shipment');
            }

            abort_unless(
                $lockedPayment->status === PaymentStatus::Pending
                    && $lockedPayment->stripe_session_id === ($session['id'] ?? null)
                    && $shipment->status === ShipmentStatus::AwaitingPayment,
                409,
            );

            $lockedPayment->forceFill([
                'status' => PaymentStatus::Succeeded,
                'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
                'paid_at' => now(),
            ])->save();

            $shipment->status = ShipmentStatus::Paid;
            $shipment->save();

            ShipmentEvent::query()->create([
                'shipment_id' => $shipment->id,
                'actor_id' => $customer->id,
                'status' => ShipmentStatus::Paid->value,
                'type' => 'payment_confirmed',
                'description' => 'Payment confirmed by direct Stripe session verification',
                'occurred_at' => now(),
            ]);

            return $lockedPayment->load('shipment');
        });

        if ($confirmedPayment->wasChanged(['status'])) {
            $customer->notify(new OperationalNotification(
                'payment_confirmed',
                'app.notifications.payment_confirmed_subject',
                'app.notifications.payment_confirmed',
                route('account.shipments.show', $confirmedPayment->shipment),
                ['tracking' => $confirmedPayment->shipment->tracking_number],
            ));
        }

        return PaymentStatus::Succeeded;
    }

    /** @param array<string, mixed> $session */
    private function assertSessionMatchesPayment(Payment $payment, array $session): void
    {
        $expectedAmount = (int) round((float) $payment->amount * 100);
        $metadata = $session['metadata'] ?? [];

        if (! is_array($metadata)
            || (string) ($session['id'] ?? '') !== (string) $payment->stripe_session_id
            || ($session['mode'] ?? null) !== 'payment'
            || (int) ($session['amount_total'] ?? -1) !== $expectedAmount
            || strtolower((string) ($session['currency'] ?? '')) !== strtolower($payment->currency)
            || (string) ($session['client_reference_id'] ?? '') !== (string) $payment->id
            || (string) ($metadata['payment_id'] ?? '') !== (string) $payment->id
            || (string) ($metadata['shipment_id'] ?? '') !== (string) $payment->shipment_id
            || (string) ($metadata['customer_id'] ?? '') !== (string) $payment->user_id) {
            throw new UnexpectedValueException('Stripe Checkout session does not match the stored payment.');
        }
    }
}