<?php

namespace Database\Seeders;

use App\Enums\UserRoles;
use App\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            AdminSeeder::class,
            SettingsSeeder::class,
        ]);

        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        // Demo customer is only created in local and test environments.
        User::updateOrCreate(
            ['email' => 'customer@tawsili.test'],
            [
                'name' => 'Demo Customer',
                'password' => Hash::make('customer123456'),
                'approval_status' => ApprovalStatus::Approved,
                'approved_at' => now(),
                'phone' => '+1000000001',
            ]
        )->syncRoles([UserRoles::Customer->value]);
    }
}
