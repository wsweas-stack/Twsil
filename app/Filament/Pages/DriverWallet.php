<?php

namespace App\Filament\Pages;

use App\Enums\UserRoles;
use App\Models\Commission;
use App\Models\User;
use App\Models\WalletTransaction;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class DriverWallet extends Page
{
    protected string $view = 'filament.pages.driver-wallet';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static ?string $navigationLabel = 'My wallet';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('app.admin_nav.wallet');
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && $user->hasInternalAccess()
            && $user->hasRole(UserRoles::Driver->value);
    }

    protected function getViewData(): array
    {
        $driver = $this->actor();
        $commissions = Commission::query()->where('driver_id', $driver->id);

        return [
            'earned' => (clone $commissions)->sum('driver_commission'),
            'paid' => (clone $commissions)->where('status', 'paid')->sum('driver_commission'),
            'unpaid' => (clone $commissions)->where('status', 'unpaid')->sum('driver_commission'),
            'transactions' => WalletTransaction::query()
                ->where('driver_id', $driver->id)
                ->latest()
                ->paginate(15),
        ];
    }

    private function actor(): User
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User && $user->hasRole(UserRoles::Driver->value), 403);

        return $user;
    }
}