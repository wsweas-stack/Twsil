<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\UserRoles;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seeder_creates_an_approved_admin_with_admin_permissions(): void
    {
        $this->seed([RoleAndPermissionSeeder::class, AdminSeeder::class]);

        $admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();

        $this->assertSame(ApprovalStatus::Approved, $admin->approval_status);
        $this->assertTrue($admin->hasRole(UserRoles::Admin->value));
        $this->assertTrue($admin->can('finance.view_reports'));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_database_seeder_does_not_create_demo_customer_outside_local_or_testing(): void
    {
        config(['app.env' => 'production']);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'admin@tawsili.test']);
        $this->assertDatabaseMissing('users', ['email' => 'customer@tawsili.test']);
    }
}