<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\DeliveryOtp;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use App\Notifications\DeliveryOtpNotification;
use App\Models\Setting;
use App\Notifications\OperationalNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class DeliveryOtpService
{
    public function __construct(private readonly CommissionService $commissions) {}

    public function issue(User $driver, Shipment $shipment): void
    {
        $this->authorizeDriver($driver, $shipment);

        if ($shipment->status !== ShipmentStatus::OutForDelivery || blank($shipment->receiver_email)) {
            throw ValidationException::withMessages([
                'otp' => __('app.shipments.otp_unavailable'),
            ]);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $ttlMinutes = (int) Setting::get(Setting::OTP_TTL_MINUTES, '10');

        DB::transaction(function () use ($driver, $shipment, $code, $ttlMinutes): void {
            $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            $this->authorizeDriver($driver, $lockedShipment);
            abort_unless($lockedShipment->status === ShipmentStatus::OutForDelivery, 409);

            DeliveryOtp::query()->updateOrCreate(
                ['shipment_id' => $lockedShipment->id],
                [
                    'code_hash' => Hash::make($code),
                    'expires_at' => now()->addMinutes($ttlMinutes),
                    'attempts' => 0,
                    'verified_at' => null,
                ],
            );

            ShipmentEvent::query()->create([
                'shipment_id' => $lockedShipment->id,
                'actor_id' => $driver->id,
                'status' => ShipmentStatus::OutForDelivery->value,
                'type' => 'delivery_otp_sent',
                'description' => 'Delivery code sent to recipient',
                'occurred_at' => now(),
            ]);
        });

        Notification::route('mail', $shipment->receiver_email)
            ->notify(new DeliveryOtpNotification($code, $shipment->tracking_number, $ttlMinutes));
    }

    public function verify(User $driver, Shipment $shipment, string $code): Shipment
    {
        $this->authorizeDriver($driver, $shipment);
        $maxAttempts = (int) Setting::get(Setting::OTP_MAX_ATTEMPTS, '3');

        $result = DB::transaction(function () use ($driver, $shipment, $code, $maxAttempts): Shipment|false {
            $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            $this->authorizeDriver($driver, $lockedShipment);
            abort_unless($lockedShipment->status === ShipmentStatus::OutForDelivery, 409);

            $otp = DeliveryOtp::query()->where('shipment_id', $lockedShipment->id)->lockForUpdate()->first();
            if ($otp === null || $otp->isExpired() || $otp->isVerified() || $otp->attempts >= $maxAttempts) {
                return false;
            }

            if (! Hash::check($code, $otp->code_hash)) {
                $otp->increment('attempts');

                return false;
            }

            $otp->verified_at = now();
            $otp->save();

            $lockedShipment->status = ShipmentStatus::Delivered;
            $lockedShipment->delivered_at = now();
            $lockedShipment->save();

            ShipmentEvent::query()->create([
                'shipment_id' => $lockedShipment->id,
                'actor_id' => $driver->id,
                'status' => ShipmentStatus::Delivered->value,
                'type' => 'delivery_otp_verified',
                'description' => 'Recipient delivery code verified',
                'occurred_at' => now(),
            ]);

            $this->commissions->recordForDeliveredShipment($lockedShipment);

            return $lockedShipment;
        });

        if ($result === false) {
            throw ValidationException::withMessages([
                'code' => __('app.shipments.otp_invalid'),
            ]);
        }

        $result->customer->notify(new OperationalNotification(
            'delivery_confirmed',
            'app.notifications.delivery_confirmed_subject',
            'app.notifications.delivery_confirmed',
            route('account.shipments.show', $result),
            ['tracking' => $result->tracking_number],
        ));

        return $result;
    }

    private function authorizeDriver(User $driver, Shipment $shipment): void
    {
        abort_unless(
            $driver->hasInternalAccess()
                && $driver->hasRole(UserRoles::Driver->value)
                && $driver->can('shipments.deliver')
                && $shipment->driver_id === $driver->id,
            403,
        );
    }
}