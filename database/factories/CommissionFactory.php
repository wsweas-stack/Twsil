<?php

namespace Database\Factories;

use App\Enums\CommissionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commission>
 */
class CommissionFactory extends Factory
{
    public function definition(): array
    {
        $fee = $this->faker->randomFloat(2, 10, 100);
        $rate = 30.00;

        return [
            'driver_id' => User::factory(),
            'delivery_fee' => $fee,
            'driver_commission' => round($fee * $rate / 100, 2),
            'company_share' => round($fee * (100 - $rate) / 100, 2),
            'driver_commission_rate' => $rate,
            'status' => CommissionStatus::Unpaid,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => CommissionStatus::Paid,
            'paid_at' => now(),
            'payment_notes' => 'Paid via factory',
        ]);
    }
}
