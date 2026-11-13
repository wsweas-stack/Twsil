<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Enums\UserRoles;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'approval_status' => ApprovalStatus::Approved,
            'approved_at' => now(),
            'phone' => fake()->unique()->numerify('+96650#######'),
            'city' => fake()->city(),
        ];
    }

    public function role(UserRoles $role): static
    {
        return $this->afterCreating(function (User $user) use ($role) {
            $user->syncRoles([\Spatie\Permission\Models\Role::findOrCreate($role->value, 'web')->name]);
        });
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'approval_status' => ApprovalStatus::Pending,
            'approved_at' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'approval_status' => ApprovalStatus::Rejected,
            'approved_at' => null,
            'rejection_reason' => 'Demo rejection',
        ]);
    }
}
