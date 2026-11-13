<?php

namespace Tests\Unit;

use App\Enums\ShipmentStatus;
use PHPUnit\Framework\TestCase;

class ShipmentStatusTest extends TestCase
{
    public function test_pending_progresses_through_the_full_lifecycle(): void
    {
        $full_path = [
            ShipmentStatus::Pending,
            ShipmentStatus::AwaitingPayment,
            ShipmentStatus::Paid,
            ShipmentStatus::Assigned,
            ShipmentStatus::PickedUp,
            ShipmentStatus::InTransit,
            ShipmentStatus::OutForDelivery,
            ShipmentStatus::Delivered,
        ];

        for ($i = 0; $i < count($full_path) - 1; $i++) {
            $this->assertTrue($full_path[$i]->canTransitionTo($full_path[$i + 1]));
        }
    }

    public function test_delivered_has_no_exits(): void
    {
        foreach (ShipmentStatus::cases() as $status) {
            if ($status === ShipmentStatus::Delivered) {
                continue;
            }

            $this->assertFalse(ShipmentStatus::Delivered->canTransitionTo($status));
        }
    }

    public function test_cancelled_has_no_exits(): void
    {
        foreach (ShipmentStatus::cases() as $status) {
            if ($status === ShipmentStatus::Cancelled) {
                continue;
            }

            $this->assertFalse(ShipmentStatus::Cancelled->canTransitionTo($status));
        }
    }

    public function test_money_backwards_steps_are_blocked(): void
    {
        // Shipments cannot revert through arbitrary jumps.
        $this->assertFalse(ShipmentStatus::Paid->canTransitionTo(ShipmentStatus::Pending));
        $this->assertFalse(ShipmentStatus::Assigned->canTransitionTo(ShipmentStatus::Paid));
        $this->assertFalse(ShipmentStatus::Delivered->canTransitionTo(ShipmentStatus::Pending));
        $this->assertFalse(ShipmentStatus::PickedUp->canTransitionTo(ShipmentStatus::AwaitingPayment));
        $this->assertFalse(ShipmentStatus::Failed->canTransitionTo(ShipmentStatus::Delivered));
    }

    public function test_failed_shipments_can_be_retried_only_through_in_transit(): void
    {
        $this->assertTrue(ShipmentStatus::Failed->canTransitionTo(ShipmentStatus::InTransit));
        $this->assertFalse(ShipmentStatus::Failed->canTransitionTo(ShipmentStatus::Assigned));
        $this->assertFalse(ShipmentStatus::Failed->canTransitionTo(ShipmentStatus::Delivered));
        $this->assertFalse(ShipmentStatus::Failed->canTransitionTo(ShipmentStatus::Pending));
    }
}
