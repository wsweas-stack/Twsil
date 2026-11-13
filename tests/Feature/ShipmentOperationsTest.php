<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentOperationsService;
use Database\Seeders\AdminSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ShipmentOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleAndPermissionSeeder::class, AdminSeeder::class]);
    }

    public function test_admin_can_quote_pending_shipment_and_event_is_recorded(): void
    {
        $admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();
        $shipment = Shipment::factory()->status(ShipmentStatus::Pending)->create();

        app(ShipmentOperationsService::class)->quote($admin, $shipment, '42.50');

        $this->assertSame(ShipmentStatus::AwaitingPayment, $shipment->fresh()->status);
        $this->assertSame('42.50', $shipment->fresh()->delivery_fee);
        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $shipment->id,
            'actor_id' => $admin->id,
            'status' => ShipmentStatus::AwaitingPayment->value,
            'type' => 'delivery_fee_quoted',
        ]);
    }

    public function test_pending_shipment_cannot_be_quoted_twice(): void
    {
        $admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();
        $shipment = Shipment::factory()->status(ShipmentStatus::Pending)->create();
        $service = app(ShipmentOperationsService::class);

        $service->quote($admin, $shipment, '42.50');

        try {
            $service->quote($admin, $shipment, '50.00');
            $this->fail('A quoted shipment must not be repriced through the initial quote action.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }

        $this->assertSame('42.50', $shipment->fresh()->delivery_fee);
    }

    public function test_admin_can_assign_only_an_approved_driver_after_payment(): void
    {
        $admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();
        $driver = User::factory()->role(UserRoles::Driver)->create([
            'approval_status' => ApprovalStatus::Approved,
        ]);
        $shipment = Shipment::factory()->status(ShipmentStatus::Paid)->create();

        app(ShipmentOperationsService::class)->assignDriver($admin, $shipment, $driver);

        $this->assertSame($driver->id, $shipment->fresh()->driver_id);
        $this->assertSame(ShipmentStatus::Assigned, $shipment->fresh()->status);
        $this->assertNotNull($shipment->fresh()->assigned_at);
        $this->assertSame(1, $driver->notifications()->count());
    }

    public function test_unapproved_driver_cannot_be_assigned(): void
    {
        $admin = User::query()->where('email', 'admin@tawsili.test')->firstOrFail();
        $driver = User::factory()->role(UserRoles::Driver)->pending()->create();
        $shipment = Shipment::factory()->status(ShipmentStatus::Paid)->create();

        try {
            app(ShipmentOperationsService::class)->assignDriver($admin, $shipment, $driver);
            $this->fail('A pending Driver must not be assigned to an operational shipment.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }

        $this->assertNull($shipment->fresh()->driver_id);
    }

    public function test_driver_operations_table_only_includes_assigned_shipments(): void
    {
        $driver = User::factory()->role(UserRoles::Driver)->create([
            'approval_status' => ApprovalStatus::Approved,
        ]);
        $otherDriver = User::factory()->role(UserRoles::Driver)->create([
            'approval_status' => ApprovalStatus::Approved,
        ]);
        $assigned = Shipment::factory()->create(['driver_id' => $driver->id]);
        $other = Shipment::factory()->create(['driver_id' => $otherDriver->id]);
        Shipment::factory()->create(['driver_id' => null]);

        $this->actingAs($driver)
            ->get('/admin/manage-shipments')
            ->assertOk()
            ->assertSee($assigned->tracking_number)
            ->assertDontSee($other->tracking_number);
    }

    public function test_employee_without_assigned_view_permission_cannot_open_operations_page(): void
    {
        $employee = User::factory()->role(UserRoles::Employee)->create([
            'approval_status' => ApprovalStatus::Approved,
        ]);

        $this->actingAs($employee)->get('/admin/manage-shipments')->assertForbidden();
    }

    public function test_employee_with_assigned_view_permission_can_open_operations_page(): void
    {
        $employee = User::factory()->role(UserRoles::Employee)->create([
            'approval_status' => ApprovalStatus::Approved,
        ]);
        $employee->givePermissionTo('shipments.view_any');
        $shipment = Shipment::factory()->create();

        $this->actingAs($employee)
            ->get('/admin/manage-shipments')
            ->assertOk()
            ->assertSee($shipment->tracking_number);
    }
}