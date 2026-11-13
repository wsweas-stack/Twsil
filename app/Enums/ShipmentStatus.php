<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Pending = 'pending';
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case Assigned = 'assigned';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    /**
     * Valid transitions enforced by ShipmentStatusService.
     * Central source of truth — receive updates only through the service.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::AwaitingPayment, self::Cancelled],
            self::AwaitingPayment => [self::Paid, self::Cancelled],
            self::Paid => [self::Assigned, self::Cancelled],
            self::Assigned => [self::PickedUp, self::Failed, self::Cancelled],
            self::PickedUp => [self::InTransit, self::Failed],
            self::InTransit => [self::OutForDelivery, self::Failed],
            self::OutForDelivery => [self::Delivered, self::Failed],
            self::Delivered => [],
            self::Cancelled => [],
            self::Failed => [self::InTransit], // retry allowed by Admin/Employee
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function label(): string
    {
        return __('app.shipment_statuses.' . $this->value);
    }

    public function isInternalStaging(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled, self::Failed], true);
    }
}
