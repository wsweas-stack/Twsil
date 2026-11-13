<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\UserRoles;
use App\Models\User;
use App\Services\StaffAccessService;
use Database\Seeders\AdminSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleAndPermissionSeeder::class, AdminSeeder::class]);
        $this->admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();
    }

    public function test_admin_can_approve_a_pending_driver(): void
    {
        $driver = User::factory()->role(UserRoles::Driver)->pending()->create();

        app(StaffAccessService::class)->approve($this->admin, $driver);

        $this->assertSame(ApprovalStatus::Approved, $driver->fresh()->approval_status);
        $this->assertNotNull($driver->fresh()->approved_at);
        $this->assertTrue($driver->fresh()->canAccessPanel(app('filament')->getPanel('admin')));
        $this->assertSame(1, $driver->notifications()->count());
    }

    public function test_admin_can_reject_a_pending_employee_with_a_reason(): void
    {
        $employee = User::factory()->role(UserRoles::Employee)->pending()->create();

        app(StaffAccessService::class)->reject($this->admin, $employee, 'Profile information needs review.');

        $this->assertSame(ApprovalStatus::Rejected, $employee->fresh()->approval_status);
        $this->assertSame('Profile information needs review.', $employee->fresh()->rejection_reason);
        $this->assertNull($employee->fresh()->approved_at);
        $this->assertFalse($employee->fresh()->canAccessPanel(app('filament')->getPanel('admin')));
        $this->assertSame(1, $employee->notifications()->count());
    }

    public function test_admin_can_assign_permissions_to_an_employee(): void
    {
        $employee = User::factory()->role(UserRoles::Employee)->create();

        app(StaffAccessService::class)->assignEmployeePermissions(
            $this->admin,
            $employee,
            ['shipments.view_any'],
        );

        $this->assertTrue($employee->fresh()->can('shipments.view_any'));
        $this->assertFalse($employee->fresh()->can('shipments.update'));
    }

    public function test_employee_cannot_be_assigned_admin_approval_permissions(): void
    {
        $employee = User::factory()->role(UserRoles::Employee)->create();

        try {
            app(StaffAccessService::class)->assignEmployeePermissions(
                $this->admin,
                $employee,
                ['users.approve'],
            );
            $this->fail('Admin-control permissions must not be delegated to Employees.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_non_admin_cannot_approve_staff(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $driver = User::factory()->role(UserRoles::Driver)->pending()->create();

        try {
            app(StaffAccessService::class)->approve($customer, $driver);
            $this->fail('Only an Admin with approval permission may approve staff.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_only_admin_can_open_the_approvals_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/manage-approvals')
            ->assertOk();

        $customer = User::factory()->role(UserRoles::Customer)->create();

        $this->actingAs($customer)
            ->get('/admin/manage-approvals')
            ->assertForbidden();
    }

    public function test_admin_can_revisit_approved_employees_for_permission_management(): void
    {
        $admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();
        $employee = User::factory()->role(UserRoles::Employee)->create([
            'approval_status' => ApprovalStatus::Approved,
        ]);

        $this->actingAs($admin)
            ->get('/admin/manage-approvals')
            ->assertOk()
            ->assertSee($employee->name)
            ->assertSee(__('app.approval_statuses.approved'));
    }

    public function test_approved_drivers_remain_visible_on_the_approvals_page(): void
    {
        $approvedDriver = User::factory()->role(UserRoles::Driver)->create([
            'approval_status' => ApprovalStatus::Approved,
        ]);
        $rejectedDriver = User::factory()->role(UserRoles::Driver)->rejected()->create();

        $this->actingAs($this->admin)
            ->get('/admin/manage-approvals')
            ->assertOk()
            ->assertSee($approvedDriver->name)
            ->assertDontSee($rejectedDriver->name);
    }
}