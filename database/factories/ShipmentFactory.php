<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    public function definition(): array
    {
        $cities = ['Riyadh', 'Jeddah', 'Dammam', 'Mecca', 'Medina'];

        return [
            'customer_id' => User::factory()->role(UserRoles::Customer),
            'tracking_number' => 'TW-' . strtoupper($this->faker->unique()->bothify('########')),
            'status' => ShipmentStatus::Paid,
            'delivery_fee' => $this->faker->randomFloat(2, 10, 100),
            'currency' => 'USD',
            'sender_name' => $this->faker->name(),
            'sender_phone' => $this->faker->numerify('+96650#######'),
            'sender_city' => $this->faker->randomElement($cities),
            'sender_address' => $this->faker->address(),
            'receiver_name' => $this->faker->name(),
            'receiver_phone' => $this->faker->numerify('+96651#######'),
            'receiver_city' => $this->faker->randomElement($cities),
            'receiver_address' => $this->faker->address(),
            'description' => 'Standard parcel — ' . $this->faker->randomElement(['documents', 'electronics', 'apparel']),
            'weight_kg' => $this->faker->randomFloat(2, 0.5, 30),
        ];
    }

    public function status(ShipmentStatus $status): static
    {
        return $this->state(fn () => ['status' => $status->value]);
    }

    public function awaitingPayment(): static
    {
        return $this->status(ShipmentStatus::AwaitingPayment);
    }

    public function delivered(): static
    {
        return $this->status(ShipmentStatus::Delivered);
    }

    public function cancelled(): static
    {
        return $this->status(ShipmentStatus::Cancelled);
    }
}
