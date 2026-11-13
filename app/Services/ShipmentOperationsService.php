<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use App\Notifications\OperationalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentOperationsService
{
    public function quote(User $actor, Shipment $shipment, string $fee): Shipment
    {
        $this->authorize($actor, 'shipments.update');

        if ((float) $fee <= 0 || $shipment->status !== ShipmentStatus::Pending) {
            throw ValidationException::withMessages([
                'delivery_fee' => __('app.shipments.quote_unavailable'),
            ]);
        }

        return DB::transaction(function () use ($actor, $shipment, $fee): Shipment {
            $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            abort_unless($lockedShipment->status === ShipmentStatus::Pending, 409);

            $lockedShipment->delivery_fee = $fee;
            $lockedShipment->currency = config('app.currency', 'SAR');
            $lockedShipment->status = ShipmentStatus::AwaitingPayment;
            $lockedShipment->save();

            $this->recordEvent($actor, $lockedShipment, ShipmentStatus::AwaitingPayment, 'delivery_fee_quoted');

            return $lockedShipment;
        });
    }

    public function assignDriver(User $actor, Shipment $shipment, User $driver): Shipment
    {
        $this->authorize($actor, 'shipments.assign_driver');
        abort_unless(
            $driver->hasRole(UserRoles::Driver->value)
                && $driver->approval_status === ApprovalStatus::Approved,
            422,
        );

        $assignedShipment = DB::transaction(function () use ($actor, $shipment, $driver): Shipment {
            $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            abort_unless(
                $lockedShipment->status === ShipmentStatus::Paid
                    && $lockedShipment->driver_id === null,
                409,
            );

            $lockedShipment->driver_id = $driver->id;
            $lockedShipment->status = ShipmentStatus::Assigned;
            $lockedShipment->assigned_at = now();
            $lockedShipment->save();

            $this->recordEvent($actor, $lockedShipment, ShipmentStatus::Assigned, 'driver_assigned');

            return $lockedShipment;
        });

        $driver->notify(new OperationalNotification(
            'driver_assigned',
            'app.notifications.driver_assigned_subject',
            'app.notifications.driver_assigned',
            url('/admin/manage-shipments'),
            ['tracking' => $assignedShipment->tracking_number],
        ));

        return $assignedShipment;
    }

    private function authorize(User $actor, string $permission): void
    {
        abort_unless($actor->hasInternalAccess() && $actor->can($permission), 403);
    }

    private function recordEvent(User $actor, Shipment $shipment, ShipmentStatus $status, string $type): void
    {
        ShipmentEvent::query()->create([
            'shipment_id' => $shipment->id,
            'actor_id' => $actor->id,
            'status' => $status->value,
            'type' => $type,
            'description' => $type === 'driver_assigned' ? 'Driver assigned' : 'Delivery fee quoted',
            'occurred_at' => now(),
        ]);
    }
}