<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use App\Notifications\OperationalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentStatusService
{
    public function transition(User $actor, Shipment $shipment, ShipmentStatus $target): Shipment
    {
        $shipment->refresh();
        $current = $shipment->status;

        if (! $current->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => __('app.shipments.invalid_transition'),
            ]);
        }

        $this->authorizeTransition($actor, $shipment, $target);

        $updatedShipment = DB::transaction(function () use ($actor, $shipment, $target): Shipment {
            $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            $lockedCurrent = $lockedShipment->status;

            if (! $lockedCurrent->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => __('app.shipments.invalid_transition'),
                ]);
            }

            $timestampColumn = match ($target) {
                ShipmentStatus::Assigned => 'assigned_at',
                ShipmentStatus::PickedUp => 'picked_up_at',
                ShipmentStatus::InTransit => 'in_transit_at',
                ShipmentStatus::OutForDelivery => 'out_for_delivery_at',
                ShipmentStatus::Delivered => 'delivered_at',
                ShipmentStatus::Cancelled => 'cancelled_at',
                default => null,
            };

            $lockedShipment->status = $target;
            if ($timestampColumn !== null) {
                $lockedShipment->{$timestampColumn} = now();
            }
            $lockedShipment->save();

            ShipmentEvent::query()->create([
                'shipment_id' => $lockedShipment->id,
                'actor_id' => $actor->id,
                'status' => $target->value,
                'type' => 'status_change',
                'description' => 'Shipment status updated',
                'occurred_at' => now(),
            ]);

            return $lockedShipment;
        });

        $updatedShipment->customer->notify(new OperationalNotification(
            'shipment_status_changed',
            'app.notifications.shipment_status_subject',
            'app.notifications.shipment_status_changed',
            route('account.shipments.show', $updatedShipment),
            [
                'tracking' => $updatedShipment->tracking_number,
                'status' => __('app.shipment_statuses.' . $target->value),
            ],
        ));

        return $updatedShipment;
    }

    private function authorizeTransition(User $actor, Shipment $shipment, ShipmentStatus $target): void
    {
        if ($actor->hasRole(UserRoles::Customer->value)) {
            abort_unless(
                $shipment->customer_id === $actor->id
                    && $target === ShipmentStatus::Cancelled
                    && in_array($shipment->status, [ShipmentStatus::Pending, ShipmentStatus::AwaitingPayment], true),
                403,
            );

            return;
        }

        abort_unless($actor->hasInternalAccess(), 403);

        if ($actor->hasRole(UserRoles::Admin->value)) {
            abort_unless($actor->can('shipments.update_status'), 403);

            return;
        }

        if ($actor->hasRole(UserRoles::Driver->value)) {
            abort_unless(
                $shipment->driver_id === $actor->id
                    && $actor->can('shipments.update_status')
                    && in_array($target, [
                        ShipmentStatus::PickedUp,
                        ShipmentStatus::InTransit,
                        ShipmentStatus::OutForDelivery,
                    ], true),
                403,
            );

            return;
        }

        abort_unless(
            $actor->hasRole(UserRoles::Employee->value) && $actor->can('shipments.update_status'),
            403,
        );
    }
}