<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Shipment;
use App\Models\ShipmentQrCode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ShipmentScanService
{
    public function __construct(private readonly ShipmentStatusService $statuses) {}

    public function scanForPickup(User $actor, string $token): Shipment
    {
        $qrCode = ShipmentQrCode::query()
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();
        $shipment = Shipment::query()->findOrFail($qrCode->shipment_id);

        abort_unless(
            $actor->hasInternalAccess()
                && (($actor->hasRole(UserRoles::Driver->value) && $shipment->driver_id === $actor->id)
                    || ($actor->hasAnyRole([UserRoles::Admin->value, UserRoles::Employee->value])
                        && $actor->can('shipments.pickup'))),
            403,
        );

        if ($shipment->status !== ShipmentStatus::Assigned) {
            throw ValidationException::withMessages([
                'token' => __('app.shipments.'.match ($shipment->status) {
                    ShipmentStatus::PickedUp,
                    ShipmentStatus::InTransit,
                    ShipmentStatus::OutForDelivery,
                    ShipmentStatus::Delivered => 'scan_already_picked',
                    ShipmentStatus::Cancelled,
                    ShipmentStatus::Failed => 'scan_cancelled',
                    default => 'scan_forbidden',
                }, [
                    'tracking' => $shipment->tracking_number,
                    'status' => $shipment->status->label(),
                ]),
            ]);
        }

        return $this->statuses->transition($actor, $shipment, ShipmentStatus::PickedUp);
    }
}