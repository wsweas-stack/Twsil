<?php

namespace App\Filament\Widgets;

use App\Enums\CommissionStatus;
use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Models\Commission;
use App\Models\Review;
use App\Models\Shipment;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class DashboardStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Operations overview';

    protected function getHeading(): ?string
    {
        return __('app.dashboard.stats_heading');
    }

    protected function getColumns(): int|array|null
    {
        $count = count($this->getCachedStats());

        return [
            '@xl' => $count,
            '!@lg' => min($count, 3),
        ];
    }

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasInternalAccess();
    }

    protected function getStats(): array
    {
        $user = Filament::auth()->user();
        abort_unless($user instanceof User, 403);

        if ($user->hasRole(UserRoles::Driver->value)) {
            return $this->driverStats($user);
        }

        return $this->operationsStats($user);
    }

    /**
     * @return array<Stat>
     */
    private function driverStats(User $user): array
    {
        $shipments = Shipment::query()->where('driver_id', $user->id);

        $activeCount = (clone $shipments)->where(function (Builder $query): void {
            $query->whereIn('status', [
                ShipmentStatus::Assigned,
                ShipmentStatus::PickedUp,
                ShipmentStatus::InTransit,
                ShipmentStatus::OutForDelivery,
            ]);
        })->count();

        $deliveredCount = (clone $shipments)->where('status', ShipmentStatus::Delivered)->count();

        $unpaidEarnings = Commission::query()
            ->where('driver_id', $user->id)
            ->where('status', CommissionStatus::Unpaid)
            ->sum('driver_commission');

        $firstCurrency = Shipment::query()
            ->where('driver_id', $user->id)
            ->value('currency');

        return [
            Stat::make(__('app.dashboard.stats.my_shipments'), (clone $shipments)->count())
                ->icon(Heroicon::OutlinedTruck)
                ->color('primary'),
            Stat::make(__('app.dashboard.stats.my_active'), $activeCount)
                ->description(__('app.dashboard.stat_descriptions.my_active'))
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->color('info'),
            Stat::make(__('app.dashboard.stats.my_delivered'), $deliveredCount)
                ->description(__('app.dashboard.stat_descriptions.my_delivered'))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make(__('app.dashboard.stats.my_unpaid'), number_format((float) $unpaidEarnings, 2) . ($firstCurrency ? ' ' . $firstCurrency : ''))
                ->icon(Heroicon::OutlinedWallet)
                ->color('warning'),
        ];
    }

    /**
     * @return array<Stat>
     */
    private function operationsStats(User $user): array
    {
        $activeStatuses = [
            ShipmentStatus::Assigned,
            ShipmentStatus::PickedUp,
            ShipmentStatus::InTransit,
            ShipmentStatus::OutForDelivery,
        ];

        $deliveredCount = Shipment::query()->where('status', ShipmentStatus::Delivered)->count();
        $deliveredRevenue = Shipment::query()->where('status', ShipmentStatus::Delivered)->sum('delivery_fee');
        $revenueCurrency = (string) (Shipment::query()->where('status', ShipmentStatus::Delivered)->value('currency') ?? '');

        $stats = [
            Stat::make(__('app.dashboard.stats.total_shipments'), Shipment::query()->count())
                ->icon(Heroicon::OutlinedTruck)
                ->color('primary'),
            Stat::make(__('app.dashboard.stats.active_shipments'), Shipment::query()->whereIn('status', $activeStatuses)->count())
                ->description(__('app.dashboard.stat_descriptions.active_shipments'))
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->color('info'),
            Stat::make(__('app.dashboard.stats.delivered_shipments'), $deliveredCount)
                ->description(__('app.dashboard.stat_descriptions.delivered_shipments_revenue', [
                    'amount' => number_format((float) $deliveredRevenue, 2) . ($revenueCurrency !== '' ? ' ' . $revenueCurrency : ''),
                ]))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
        ];

        if ($user->hasRole(UserRoles::Admin->value)) {
            $pendingApprovals = User::query()
                ->where('approval_status', \App\Enums\ApprovalStatus::Pending)
                ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', [UserRoles::Driver->value, UserRoles::Employee->value]))
                ->count();

            $pendingReviews = Review::query()->where('status', 'pending')->count();

            $stats[] = Stat::make(__('app.dashboard.stats.pending_approvals'), $pendingApprovals)
                ->description(__('app.dashboard.stat_descriptions.pending_approvals'))
                ->icon(Heroicon::OutlinedUserPlus)
                ->color($pendingApprovals > 0 ? 'warning' : 'gray');

            $stats[] = Stat::make(__('app.dashboard.stats.pending_reviews'), $pendingReviews)
                ->description(__('app.dashboard.stat_descriptions.pending_reviews'))
                ->icon(Heroicon::OutlinedStar)
                ->color($pendingReviews > 0 ? 'danger' : 'gray');
        }

        return $stats;
    }
}
