<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'user_id' => function (array $attributes) {
                return Shipment::find($attributes['shipment_id'])->customer_id;
            },
            'amount' => $this->faker->randomFloat(2, 10, 100),
            'currency' => 'USD',
            'status' => PaymentStatus::Succeeded,
            'stripe_session_id' => 'cs_test_' . $this->faker->unique()->regexify('[A-Za-z0-9]{24}'),
            'stripe_payment_intent_id' => 'pi_test_' . $this->faker->unique()->regexify('[A-Za-z0-9]{24}'),
            'paid_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Pending,
            'stripe_session_id' => 'cs_test_' . $this->faker->unique()->regexify('[A-Za-z0-9]{24}'),
            'stripe_payment_intent_id' => null,
            'paid_at' => null,
        ]);
    }
}
