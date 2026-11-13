<?php

namespace App\Filament\Pages;

use App\Enums\CommissionStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Commission;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\FinanceService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rule;

class ManageFinances extends Page
{
    protected string $view = 'filament.pages.manage-finances';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Finance';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('app.admin_nav.finance');
    }

    public ?string $driverFilter = null;

    public ?string $fromDate = null;

    public ?string $toDate = null;

    public string $payoutNotes = '';

    protected function rules(): array
    {
        return [
            'driverFilter' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'fromDate' => ['nullable', 'date'],
            'toDate' => ['nullable', 'date', 'after_or_equal:fromDate'],
        ];
    }

    public function updatedDriverFilter(): void
    {
        $this->driverFilter = filled($this->driverFilter) ? $this->driverFilter : null;
        $this->validateOnly('driverFilter');
    }

    public function updatedFromDate(): void
    {
        $this->fromDate = filled($this->fromDate) ? $this->fromDate : null;
        $this->toDate = filled($this->toDate) ? $this->toDate : null;
        $this->validateOnly('fromDate');
        $this->validateOnly('toDate');
    }

    public function updatedToDate(): void
    {
        $this->fromDate = filled($this->fromDate) ? $this->fromDate : null;
        $this->toDate = filled($this->toDate) ? $this->toDate : null;
        $this->validateOnly('toDate');
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && $user->hasInternalAccess()
            && $user->hasAnyRole([UserRoles::Admin->value, UserRoles::Employee->value])
            && $user->can('finance.view_reports');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('configureRates')
                ->label(__('app.finance.configure_rates'))
                ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                ->visible(fn (): bool => $this->actor()->hasRole(UserRoles::Admin->value)
                    && $this->actor()->can('finance.configure_rates'))
                ->fillForm(fn (): array => [
                    'driver_rate' => Setting::get(Setting::DRIVER_COMMISSION_RATE, '30'),
                    'company_rate' => Setting::get(Setting::COMPANY_SHARE_RATE, '70'),
                ])
                ->form([
                    TextInput::make('driver_rate')->label(__('app.finance.driver_rate'))->numeric()->minValue(0)->maxValue(100)->required(),
                    TextInput::make('company_rate')->label(__('app.finance.company_rate'))->numeric()->minValue(0)->maxValue(100)->required(),
                ])
                ->action(function (array $data): void {
                    app(FinanceService::class)->configureRates(
                        $this->actor(),
                        (string) $data['driver_rate'],
                        (string) $data['company_rate'],
                    );
                    Notification::make()->title(__('app.finance.rates_saved'))->success()->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $commissions = Commission::query()
            ->with(['driver:id,name', 'shipment:id,tracking_number'])
            ->when($this->driverFilter, fn (Builder $query, string $driverId) => $query->where('driver_id', $driverId))
            ->when($this->fromDate, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($this->toDate, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest();

        return [
            'totalRevenue' => Shipment::query()->where('status', ShipmentStatus::Delivered)->sum('delivery_fee'),
            'totalDriverCommissions' => Commission::query()->sum('driver_commission'),
            'totalCompanyShare' => Commission::query()->sum('company_share'),
            'totalPaidCommissions' => Commission::query()->where('status', CommissionStatus::Paid)->sum('driver_commission'),
            'totalUnpaidCommissions' => Commission::query()->where('status', CommissionStatus::Unpaid)->sum('driver_commission'),
            'drivers' => User::query()->role(UserRoles::Driver->value)->orderBy('name')->get(['id', 'name']),
            'commissions' => $commissions->paginate(15),
        ];
    }

    public function markPaid(int $commissionId): void
    {
        $this->validate([
            'payoutNotes' => ['required', 'string', 'max:1000'],
        ]);

        $commission = Commission::query()->findOrFail($commissionId);
        app(FinanceService::class)->markCommissionPaid($this->actor(), $commission, $this->payoutNotes);
        $this->payoutNotes = '';
        $this->resetValidation('payoutNotes');

        Notification::make()->title(__('app.finance.payout_saved'))->success()->send();
    }

    private function actor(): User
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User && $user->hasInternalAccess(), 403);

        return $user;
    }
}