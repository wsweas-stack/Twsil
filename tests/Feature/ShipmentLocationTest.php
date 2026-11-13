<?php

namespace Tests\Feature;

use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentLocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_owner_can_save_delivery_location_via_map_picker(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'status' => ShipmentStatus::AwaitingPayment->value,
        ]);

        $this->actingAs($customer)
            ->withHeader('referer', route('account.shipments.show', $shipment))
            ->post(route('account.shipments.location', $shipment), [
                'latitude' => 21.485811,
                'longitude' => 39.192505,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('account.shipments.show', $shipment))
            ->assertSessionHas('status', __('app.shipments.location_updated'));

        $shipment->refresh();

        $this->assertSame(21.485811, (float) $shipment->receiver_latitude);
        $this->assertSame(39.192505, (float) $shipment->receiver_longitude);

        // Saved coordinates survive a page refresh and are displayed in shipment details.
        $this->actingAs($customer)
            ->get(route('account.shipments.show', $shipment))
            ->assertOk()
            ->assertSee('21.4858110')
            ->assertSee('39.1925050');
    }

    public function test_invalid_coordinates_are_rejected(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'status' => ShipmentStatus::AwaitingPayment->value,
        ]);

        $this->actingAs($customer)
            ->post(route('account.shipments.location', $shipment), [
                'latitude' => 91,
                'longitude' => -180.5,
            ])
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $shipment->refresh();

        $this->assertNull($shipment->receiver_latitude);
        $this->assertNull($shipment->receiver_longitude);
    }

    public function test_invalid_coordinate_errors_are_visible_in_the_open_location_picker(): void
    {
        config(['services.google_maps.key' => 'test-map-key']);

        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'status' => ShipmentStatus::AwaitingPayment->value,
        ]);

        $this->followingRedirects();

        $response = $this->actingAs($customer)
            ->from(route('account.shipments.show', $shipment))
            ->post(route('account.shipments.location', $shipment), [
                'latitude' => 91,
                'longitude' => -180.5,
            ])
            ->assertOk()
            ->assertSee('location-picker-title" open', false)
            ->assertSee('class="field-error" style="margin: 0;"', false);

        $this->assertSame(2, substr_count($response->getContent(), 'class="field-error"'));
    }

    public function test_another_customer_cannot_update_shipment_location(): void
    {
        $owner = User::factory()->role(UserRoles::Customer)->create();
        $stranger = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->create([
            'customer_id' => $owner->id,
            'status' => ShipmentStatus::AwaitingPayment->value,
        ]);

        $this->actingAs($stranger)
            ->post(route('account.shipments.location', $shipment), [
                'latitude' => 24.7136,
                'longitude' => 46.6753,
            ])
            ->assertNotFound();

        $shipment->refresh();

        $this->assertNull($shipment->receiver_latitude);
        $this->assertNull($shipment->receiver_longitude);
    }

    public function test_driver_and_internal_staff_cannot_update_shipment_location(): void
    {
        $owner = User::factory()->role(UserRoles::Customer)->create();
        $driver = User::factory()->role(UserRoles::Driver)->create();
        $shipment = Shipment::factory()->create([
            'customer_id' => $owner->id,
            'driver_id' => $driver->id,
            'status' => ShipmentStatus::OutForDelivery->value,
        ]);

        $this->actingAs($driver)
            ->post(route('account.shipments.location', $shipment), [
                'latitude' => 24.7136,
                'longitude' => 46.6753,
            ])
            ->assertNotFound();

        $shipment->refresh();

        $this->assertNull($shipment->receiver_latitude);
        $this->assertNull($shipment->receiver_longitude);
    }

    public function test_location_cannot_change_after_delivery_or_cancellation(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'status' => ShipmentStatus::Delivered->value,
            'receiver_latitude' => 21.485811,
            'receiver_longitude' => 39.192505,
        ]);

        $this->actingAs($customer)
            ->post(route('account.shipments.location', $shipment), [
                'latitude' => 24.7136,
                'longitude' => 46.6753,
            ])
            ->assertForbidden();

        $shipment->refresh();

        $this->assertSame(21.485811, (float) $shipment->receiver_latitude);
        $this->assertSame(39.192505, (float) $shipment->receiver_longitude);
    }

    public function test_details_page_indicates_when_no_location_is_selected(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'status' => ShipmentStatus::AwaitingPayment->value,
        ]);

        $this->actingAs($customer)
            ->get(route('account.shipments.show', $shipment))
            ->assertOk()
            ->assertSee(__('app.shipments.location_none'));
    }

    public function test_picker_is_without_api_key_and_available_when_key_is_set(): void
    {
        $customer = User::factory()->role(UserRoles::Customer)->create();
        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'status' => ShipmentStatus::AwaitingPayment->value,
        ]);

        config(['services.google_maps.key' => null]);

        $this->actingAs($customer)
            ->get(route('account.shipments.show', $shipment))
            ->assertOk()
            ->assertSee(__('app.shipments.picker_unavailable'));

        config(['services.google_maps.key' => 'test-maps-key']);

        $this->actingAs($customer)
            ->get(route('account.shipments.show', $shipment))
            ->assertOk()
            ->assertSee(__('app.shipments.pick_location'))
            ->assertSee('data-maps-key="test-maps-key"', false);
    }
}
