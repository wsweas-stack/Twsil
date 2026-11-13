<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory()->delivered(),
            'user_id' => function (array $attributes) {
                return Shipment::find($attributes['shipment_id'])->customer_id;
            },
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->boolean(70) ? $this->faker->sentence() : null,
            'status' => 'approved',
        ];
    }
}
