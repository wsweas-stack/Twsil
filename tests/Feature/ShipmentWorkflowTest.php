<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use App\Services\ShipmentStatusService;
use Database\Seeders\RoleAndPermissionSeeder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ShipmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_customer_creates_shipment_with_server_owned_values_and_private_invoice_fallback(): void
    {
        Storage::fake('local');
        $customer = User::factory()->role(UserRoles::Customer)->create();

        $this->actingAs($customer)
            ->post(route('account.shipments.store'), $this->shipmentData([
                'customer_id' => 99999,
                'driver_id' => 99999,
                'tracking_number' => 'FORGED-TRACKING',
                'delivery_fee' => 0.01,
                'status' => ShipmentStatus::Delivered->value,
                'invoice' => UploadedFile::fake()->create('customer-invoice.pdf', 10, 'application/pdf'),
            ]))
            ->assertSessionHasNoErrors();

        $shipment = Shipment::query()->firstOrFail();

        $this->assertSame($customer->id, $shipment->customer_id);
        $this->assertNull($shipment->driver_id);
        $this->assertNotSame('FORGED-TRACKING', $shipment->tracking_number);
        $this->assertSame(ShipmentStatus::Pending, $shipment->status);
        $this->assertSame('0.00', $shipment->delivery_fee);
        $this->assertNotNull($shipment->invoice_path);
        $this->assertNull($shipment->invoice_extracted_text);
        $this->assertTrue(Storage::disk('local')->exists($shipment->invoice_path));
        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $shipment->id,
            'actor_id' => $customer->id,
            'status' => ShipmentStatus::Pending->value,
            'type' => 'shipment_created',
        ]);
        $this->assertSame(1, $customer->notifications()->count());
    }

    public function test_valid_pdf_invoice_text_is_extracted_and_saved(): void
    {
        Storage::fake('local');
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $pdfContents = Pdf::loadHTML('<h1>Invoice REF-7421</h1><p>Delivery fee: 42.50 SAR</p>')->output();
        $invoice = UploadedFile::fake()->createWithContent('invoice.pdf', $pdfContents);

        $this->actingAs($customer)
            ->post(route('account.shipments.store'), $this->shipmentData(['invoice' => $invoice]))
            ->assertSessionHasNoErrors();

        $shipment = Shipment::query()->firstOrFail();
        $this->assertStringContainsString('REF-7421', (string) $shipment->invoice_extracted_text);
        $this->assertStringContainsString('42.50', (string) $shipment->invoice_extracted_text);
    }

    public function test_customer_can_only_see_their_own_shipments_and_invoice(): void
    {
        Storage::fake('local');
        $owner = User::factory()->role(UserRoles::Customer)->create();
        $other = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->create([
            'customer_id' => $owner->id,
            'invoice_path' => 'shipments/' . $owner->id . '/invoice.pdf',
        ]);
        Storage::disk('local')->put($shipment->invoice_path, '%PDF');

        $this->actingAs($owner)
            ->get(route('account.shipments.show', $shipment))
            ->assertOk();

        $this->actingAs($other)
            ->get(route('account.shipments.show', $shipment))
            ->assertNotFound();

        $this->get(route('account.shipments.invoice', $shipment))
            ->assertNotFound();
    }

    public function test_shipment_history_is_scoped_to_the_authenticated_customer(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $other = User::factory()->role(UserRoles::Customer)->create();
        $ownShipment = Shipment::factory()->create(['customer_id' => $customer->id]);
        $otherShipment = Shipment::factory()->create(['customer_id' => $other->id]);

        $this->actingAs($customer)
            ->get(route('account.shipments.index'))
            ->assertOk()
            ->assertSee($ownShipment->tracking_number)
            ->assertDontSee($otherShipment->tracking_number);
    }

    public function test_customer_can_cancel_only_their_own_pending_shipment(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'status' => ShipmentStatus::Pending,
        ]);

        $this->actingAs($customer)
            ->post(route('account.shipments.cancel', $shipment))
            ->assertRedirect();

        $this->assertSame(ShipmentStatus::Cancelled, $shipment->fresh()->status);
        $this->assertNotNull($shipment->fresh()->cancelled_at);
        $this->assertDatabaseHas('shipment_events', [
            'shipment_id' => $shipment->id,
            'status' => ShipmentStatus::Cancelled->value,
        ]);
        $this->assertSame(1, $customer->notifications()->count());
    }

    public function test_invalid_status_transition_is_rejected_without_mutation(): void
    {
        $admin = User::factory()->role(UserRoles::Admin)->create();
        $shipment = Shipment::factory()->status(ShipmentStatus::Paid)->create();

        try {
            app(ShipmentStatusService::class)->transition($admin, $shipment, ShipmentStatus::Delivered);
            $this->fail('A shipment cannot skip assigned, pickup, and delivery stages.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame(ShipmentStatus::Paid, $shipment->fresh()->status);
        $this->assertSame(0, $shipment->events()->count());
    }

    public function test_driver_can_update_only_an_assigned_shipment_through_permitted_statuses(): void
    {
        $driver = User::factory()->role(UserRoles::Driver)->create([
            'approval_status' => ApprovalStatus::Approved,
        ]);
        $assigned = Shipment::factory()->status(ShipmentStatus::Assigned)->create(['driver_id' => $driver->id]);
        $unassigned = Shipment::factory()->status(ShipmentStatus::Assigned)->create();
        $readyForDelivery = Shipment::factory()->status(ShipmentStatus::OutForDelivery)->create(['driver_id' => $driver->id]);

        app(ShipmentStatusService::class)->transition($driver, $assigned, ShipmentStatus::PickedUp);

        $this->assertSame(ShipmentStatus::PickedUp, $assigned->fresh()->status);

        try {
            app(ShipmentStatusService::class)->transition($driver, $unassigned, ShipmentStatus::PickedUp);
            $this->fail('A Driver cannot update a shipment assigned to another Driver.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        try {
            app(ShipmentStatusService::class)->transition($driver, $readyForDelivery, ShipmentStatus::Delivered);
            $this->fail('A Driver cannot skip OTP delivery confirmation.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_customer_must_supply_all_required_address_and_parcel_fields(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();

        $this->actingAs($customer)
            ->post(route('account.shipments.store'), [])
            ->assertSessionHasErrors([
                'sender_name', 'sender_phone', 'sender_city', 'sender_address',
                'receiver_name', 'receiver_phone', 'receiver_city', 'receiver_address', 'description',
            ]);

        $this->assertDatabaseCount('shipments', 0);
    }

    private function shipmentData(array $overrides = []): array
    {
        return array_merge([
            'sender_name' => 'Sender Name',
            'sender_phone' => '+966500000001',
            'sender_city' => 'Riyadh',
            'sender_address' => 'Sender address, building 1',
            'receiver_name' => 'Receiver Name',
            'receiver_phone' => '+966500000002',
            'receiver_city' => 'Jeddah',
            'receiver_address' => 'Receiver address, building 2',
            'description' => 'Documents',
            'weight_kg' => 1.25,
        ], $overrides);
    }
}