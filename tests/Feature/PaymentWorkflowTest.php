<?php

namespace Tests\Feature;

use App\Contracts\CheckoutSessionGateway;
use App\Enums\PaymentStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_customer_checkout_uses_stored_amount_and_metadata(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->status(ShipmentStatus::AwaitingPayment)->create([
            'customer_id' => $customer->id,
            'delivery_fee' => '42.50',
            'currency' => 'SAR',
        ]);
        $gateway = Mockery::mock(CheckoutSessionGateway::class);
        $gateway->shouldReceive('create')
            ->once()
            ->withArgs(function (Payment $payment, Shipment $gatewayShipment) use ($shipment, $customer): bool {
                $this->assertSame($shipment->id, $gatewayShipment->id);
                $this->assertSame($customer->id, $payment->user_id);
                $this->assertSame('42.50', $payment->amount);
                $this->assertSame('SAR', $payment->currency);

                return true;
            })
            ->andReturn(['id' => 'cs_test_checkout_1', 'url' => 'https://checkout.stripe.test/session']);
        $this->app->instance(CheckoutSessionGateway::class, $gateway);

        $this->actingAs($customer)
            ->post(route('payments.checkout', $shipment))
            ->assertRedirect('https://checkout.stripe.test/session');

        $this->assertDatabaseHas('payments', [
            'shipment_id' => $shipment->id,
            'user_id' => $customer->id,
            'amount' => '42.50',
            'currency' => 'SAR',
            'status' => PaymentStatus::Pending->value,
            'stripe_session_id' => 'cs_test_checkout_1',
        ]);
    }

    public function test_customer_cannot_create_checkout_for_another_customers_shipment(): void
    {
        $owner = User::factory()->role(UserRoles::Customer)->create();
        $other = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->status(ShipmentStatus::AwaitingPayment)->create([
            'customer_id' => $owner->id,
            'delivery_fee' => '10.00',
        ]);

        $this->actingAs($other)
            ->post(route('payments.checkout', $shipment))
            ->assertNotFound();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_customer_confirms_payment_against_stripe_and_updates_shipment_once(): void
    {
        [$customer, $shipment, $payment] = $this->pendingPayment();
        $gateway = Mockery::mock(CheckoutSessionGateway::class);
        $gateway->shouldReceive('retrieve')
            ->once()
            ->with($payment->stripe_session_id)
            ->andReturn($this->stripeSession($payment, $shipment));
        $this->app->instance(CheckoutSessionGateway::class, $gateway);

        $this->actingAs($customer)
            ->post(route('payments.confirm', $payment))
            ->assertRedirect(route('payments.result', ['payment_id' => $payment->id]))
            ->assertSessionHas('payment_status', PaymentStatus::Succeeded->value);

        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertSame(ShipmentStatus::Paid, $shipment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $shipment->id,
            'actor_id' => $customer->id,
            'type' => 'payment_confirmed',
            'status' => ShipmentStatus::Paid->value,
        ]);

        $this->actingAs($customer)
            ->post(route('payments.confirm', $payment))
            ->assertRedirect(route('payments.result', ['payment_id' => $payment->id]));

        $this->assertSame(1, $shipment->events()->where('type', 'payment_confirmed')->count());
        $this->assertSame(1, $customer->notifications()->count());
    }

    public function test_customer_cannot_confirm_another_customers_payment(): void
    {
        [, , $payment] = $this->pendingPayment();
        $otherCustomer = User::factory()->role(UserRoles::Customer)->create();

        $this->actingAs($otherCustomer)
            ->post(route('payments.confirm', $payment))
            ->assertNotFound();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_unpaid_stripe_session_does_not_confirm_payment(): void
    {
        [$customer, $shipment, $payment] = $this->pendingPayment();
        $session = $this->stripeSession($payment, $shipment);
        $session['status'] = 'open';
        $session['payment_status'] = 'unpaid';
        $gateway = Mockery::mock(CheckoutSessionGateway::class);
        $gateway->shouldReceive('retrieve')->once()->andReturn($session);
        $this->app->instance(CheckoutSessionGateway::class, $gateway);

        $this->actingAs($customer)
            ->post(route('payments.confirm', $payment))
            ->assertRedirect(route('payments.result', ['payment_id' => $payment->id]))
            ->assertSessionHas('payment_status', PaymentStatus::Pending->value);

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(ShipmentStatus::AwaitingPayment, $shipment->fresh()->status);
        $this->assertDatabaseCount('shipment_events', 0);
    }

    public function test_mismatched_stripe_amount_does_not_confirm_payment(): void
    {
        [$customer, $shipment, $payment] = $this->pendingPayment();
        $session = $this->stripeSession($payment, $shipment);
        $session['amount_total'] = 1;
        $gateway = Mockery::mock(CheckoutSessionGateway::class);
        $gateway->shouldReceive('retrieve')->once()->andReturn($session);
        $this->app->instance(CheckoutSessionGateway::class, $gateway);

        $this->actingAs($customer)
            ->post(route('payments.confirm', $payment))
            ->assertServerError();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(ShipmentStatus::AwaitingPayment, $shipment->fresh()->status);
        $this->assertDatabaseCount('shipment_events', 0);
    }

    public function test_customer_cancels_open_checkout_by_expiring_the_stripe_session(): void
    {
        [$customer, $shipment, $payment] = $this->pendingPayment();
        $openSession = $this->stripeSession($payment, $shipment);
        $openSession['status'] = 'open';
        $openSession['payment_status'] = 'unpaid';
        $expiredSession = $openSession;
        $expiredSession['status'] = 'expired';
        $gateway = Mockery::mock(CheckoutSessionGateway::class);
        $gateway->shouldReceive('retrieve')->once()->andReturn($openSession);
        $gateway->shouldReceive('expire')
            ->once()
            ->with($payment->stripe_session_id)
            ->andReturn($expiredSession);
        $this->app->instance(CheckoutSessionGateway::class, $gateway);

        $this->actingAs($customer)
            ->post(route('payments.cancel', $payment))
            ->assertRedirect(route('payments.result', ['payment_id' => $payment->id]))
            ->assertSessionHas('payment_status', PaymentStatus::Cancelled->value);

        $this->assertSame(PaymentStatus::Cancelled, $payment->fresh()->status);
        $this->assertSame(ShipmentStatus::AwaitingPayment, $shipment->fresh()->status);
        $this->assertNull($payment->fresh()->paid_at);
        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $shipment->id,
            'type' => 'payment_cancelled',
            'status' => ShipmentStatus::AwaitingPayment->value,
        ]);
    }

    public function test_cancel_action_does_not_cancel_a_paid_stripe_session(): void
    {
        [$customer, $shipment, $payment] = $this->pendingPayment();
        $gateway = Mockery::mock(CheckoutSessionGateway::class);
        $gateway->shouldReceive('retrieve')
            ->once()
            ->andReturn($this->stripeSession($payment, $shipment));
        $gateway->shouldNotReceive('expire');
        $this->app->instance(CheckoutSessionGateway::class, $gateway);

        $this->actingAs($customer)
            ->post(route('payments.cancel', $payment))
            ->assertRedirect(route('payments.result', ['payment_id' => $payment->id]))
            ->assertSessionHas('payment_status', PaymentStatus::Succeeded->value);

        $this->assertSame(PaymentStatus::Succeeded, $payment->fresh()->status);
        $this->assertSame(ShipmentStatus::Paid, $shipment->fresh()->status);
    }

    public function test_payment_return_url_alone_does_not_confirm_payment(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->status(ShipmentStatus::AwaitingPayment)->create([
            'customer_id' => $customer->id,
        ]);
        $payment = Payment::query()->create([
            'shipment_id' => $shipment->id,
            'user_id' => $customer->id,
            'amount' => '10.00',
            'currency' => 'SAR',
            'status' => PaymentStatus::Pending,
            'stripe_session_id' => 'cs_test_return_only',
        ]);

        $this->actingAs($customer)
            ->get(route('payments.result', ['session_id' => $payment->stripe_session_id]))
            ->assertOk()
            ->assertSee(__('app.payments.confirm_button'))
            ->assertSee(__('app.payments.cancel_button'));

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(ShipmentStatus::AwaitingPayment, $shipment->fresh()->status);
    }

    /** @return array{User, Shipment, Payment} */
    private function pendingPayment(): array
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->status(ShipmentStatus::AwaitingPayment)->create([
            'customer_id' => $customer->id,
            'delivery_fee' => '42.50',
            'currency' => 'SAR',
        ]);
        $payment = Payment::query()->create([
            'shipment_id' => $shipment->id,
            'user_id' => $customer->id,
            'amount' => '42.50',
            'currency' => 'SAR',
            'status' => PaymentStatus::Pending,
            'stripe_session_id' => 'cs_test_' . $shipment->id,
        ]);

        return [$customer, $shipment, $payment];
    }

    /** @return array<string, mixed> */
    private function stripeSession(Payment $payment, Shipment $shipment): array
    {
        return [
            'id' => $payment->stripe_session_id,
            'mode' => 'payment',
            'status' => 'complete',
            'payment_status' => 'paid',
            'amount_total' => 4250,
            'currency' => 'sar',
            'client_reference_id' => (string) $payment->id,
            'payment_intent' => 'pi_test_' . $shipment->id,
            'metadata' => [
                'payment_id' => (string) $payment->id,
                'shipment_id' => (string) $shipment->id,
                'customer_id' => (string) $payment->user_id,
            ],
        ];
    }
}
