<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Enums\UserRoles;
use App\Enums\WalletTransactionType;
use App\Models\Commission;
use App\Models\Setting;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FinanceService
{
    public function markCommissionPaid(User $actor, Commission $commission, string $notes): Commission
    {
        $this->authorize($actor, 'finance.pay_commissions');
        Validator::make(
            ['notes' => $notes],
            ['notes' => ['required', 'string', 'max:1000']],
        )->validate();

        return DB::transaction(function () use ($commission, $notes): Commission {
            $lockedCommission = Commission::query()->lockForUpdate()->findOrFail($commission->id);
            abort_unless($lockedCommission->status === CommissionStatus::Unpaid, 409);

            $lockedCommission->status = CommissionStatus::Paid;
            $lockedCommission->paid_at = now();
            $lockedCommission->payment_notes = $notes;
            $lockedCommission->save();

            WalletTransaction::query()->create([
                'driver_id' => $lockedCommission->driver_id,
                'commission_id' => null,
                'type' => WalletTransactionType::Payout,
                'amount' => '-' . $lockedCommission->driver_commission,
                'description' => __('app.finance.payout_note', ['shipment' => $lockedCommission->shipment_id]),
            ]);

            return $lockedCommission;
        });
    }

    public function configureRates(User $actor, string $driverRate, string $companyRate): void
    {
        $this->authorize($actor, 'finance.configure_rates');

        if (! is_numeric($driverRate) || ! is_numeric($companyRate)
            || (float) $driverRate < 0 || (float) $driverRate > 100
            || (float) $companyRate < 0 || (float) $companyRate > 100
            || abs(((float) $driverRate + (float) $companyRate) - 100) > 0.0001) {
            throw ValidationException::withMessages([
                'driver_commission_rate' => __('app.finance.invalid_rates'),
            ]);
        }

        Setting::set(Setting::DRIVER_COMMISSION_RATE, number_format((float) $driverRate, 2, '.', ''));
        Setting::set(Setting::COMPANY_SHARE_RATE, number_format((float) $companyRate, 2, '.', ''));
    }

    private function authorize(User $actor, string $permission): void
    {
        abort_unless(
            $actor->hasInternalAccess()
                && $actor->hasRole(UserRoles::Admin->value)
                && $actor->can($permission),
            403,
        );
    }
}