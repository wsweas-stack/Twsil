<?php

namespace Tests\Feature;

use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Commission;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShipmentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipment_belongs_to_customer_and_driver(): void
    {
        $shipment = Shipment::factory()->create();

        $this->assertInstanceOf(User::class, $shipment->customer);
        $this->assertTrue($shipment->customer->hasRole(UserRoles::Customer->value));
        $this->assertNull($shipment->driver);
    }

    public function test_tracking_numbers_are_unique(): void
    {
        $first = Shipment::factory()->create();
        $second = Shipment::factory()->create();

        $this->assertNotSame($first->tracking_number, $second->tracking_number);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('shipments')->insert([
            'customer_id' => $first->customer_id,
            'tracking_number' => $first->tracking_number,
            'status' => ShipmentStatus::Pending->value,
            'sender_name' => 'A', 'sender_phone' => '1', 'sender_city' => 'X', 'sender_address' => 'a',
            'receiver_name' => 'B', 'receiver_phone' => '2', 'receiver_city' => 'X', 'receiver_address' => 'b',
            'description' => 'dup test',
        ]);
    }

    public function test_payment_commission_and_review_are_one_per_shipment(): void
    {
        $shipment = Shipment::factory()->create();

        $payment = Payment::factory()->create(['shipment_id' => $shipment->id]);
        $commission = Commission::factory()->create(['shipment_id' => $shipment->id]);
        $review = Review::factory()->create(['shipment_id' => $shipment->id]);

        $this->assertSame($payment->id, $shipment->payment->id);
        $this->assertSame($commission->id, $shipment->commission->id);
        $this->assertSame($review->id, $shipment->review->id);

        // DB-level uniqueness guard: a second commission for the same shipment must fail.
        $this->expectException(\Illuminate\Database\QueryException::class);
        Commission::factory()->create(['shipment_id' => $shipment->id]);
    }
}
