<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentEvent>
 */
class ShipmentEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'actor_id' => User::factory(),
            'status' => 'pending',
            'type' => 'status_change',
            'description' => $this->faker->sentence(),
            'occurred_at' => now(),
        ];
    }
}
