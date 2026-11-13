<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Enums\ShipmentStatus;
use App\Enums\WalletTransactionType;
use App\Models\Commission;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommissionService
{
    public function recordForDeliveredShipment(Shipment $shipment): Commission
    {
        return DB::transaction(function () use ($shipment): Commission {
            $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
            if ($lockedShipment->status !== ShipmentStatus::Delivered || $lockedShipment->driver_id === null) {
                abort(409);
            }

            $commission = Commission::query()
                ->where('shipment_id', $lockedShipment->id)
                ->lockForUpdate()
                ->first();

            if ($commission !== null) {
                return $commission;
            }

            $driverRate = (float) Setting::get(Setting::DRIVER_COMMISSION_RATE, '30');
            $companyRate = (float) Setting::get(Setting::COMPANY_SHARE_RATE, '70');

            if ($driverRate < 0 || $companyRate < 0 || abs(($driverRate + $companyRate) - 100) > 0.0001) {
                throw ValidationException::withMessages([
                    'commission_rate' => __('app.finance.invalid_rates'),
                ]);
            }

            $deliveryCents = (int) round((float) $lockedShipment->delivery_fee * 100);
            $driverRateBasisPoints = (int) round($driverRate * 100);
            $driverCommissionCents = intdiv(($deliveryCents * $driverRateBasisPoints) + 5000, 10000);
            $companyShareCents = $deliveryCents - $driverCommissionCents;

            $commission = Commission::query()->create([
                'shipment_id' => $lockedShipment->id,
                'driver_id' => $lockedShipment->driver_id,
                'delivery_fee' => $this->asAmount($deliveryCents),
                'driver_commission' => $this->asAmount($driverCommissionCents),
                'company_share' => $this->asAmount($companyShareCents),
                'driver_commission_rate' => number_format($driverRate, 2, '.', ''),
                'status' => CommissionStatus::Unpaid,
            ]);

            WalletTransaction::query()->create([
                'driver_id' => $commission->driver_id,
                'commission_id' => $commission->id,
                'type' => WalletTransactionType::Commission,
                'amount' => $commission->driver_commission,
                'description' => __('app.finance.wallet_commission', ['tracking' => $lockedShipment->tracking_number]),
            ]);

            return $commission;
        });
    }

    private function asAmount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}