<?php

namespace Database\Seeders;

use App\Enums\UserRoles;
use App\Enums\ApprovalStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    /**
     * The ONLY way an Admin account is created (see AGENTS.md §4).
        * Password is read from ADMIN_PASSWORD and must be at least 12 characters.
     */
    public function run(): void
    {
        $password = (string) env('ADMIN_PASSWORD', '');

        if (strlen($password) < 12) {
            throw new RuntimeException('Set ADMIN_PASSWORD to at least 12 characters before creating the Admin account.');
        }

        $admin = \App\Models\User::updateOrCreate(
            ['email' => 'admin@tawsili.test'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make($password),
                'approval_status' => ApprovalStatus::Approved,
                'approved_at' => now(),
                'phone' => '+1000000000',
            ]
        );

        $admin->syncRoles([UserRoles::Admin->value]);
    }
}
