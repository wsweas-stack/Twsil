<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\DriverWallet;
use App\Filament\Pages\ManageApprovals;
use App\Filament\Pages\ManageFinances;
use App\Filament\Pages\ManageReviews;
use App\Filament\Pages\ManageShipments;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class DashboardQuickActionsWidget extends Widget
{
    protected string $view = 'filament.widgets.dashboard-quick-actions';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasInternalAccess();
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $candidates = [
            [
                'page' => ManageShipments::class,
                'icon' => \Filament\Support\Icons\Heroicon::OutlinedTruck,
            ],
            [
                'page' => ManageApprovals::class,
                'icon' => \Filament\Support\Icons\Heroicon::OutlinedUserPlus,
            ],
            [
                'page' => ManageFinances::class,
                'icon' => \Filament\Support\Icons\Heroicon::OutlinedBanknotes,
            ],
            [
                'page' => ManageReviews::class,
                'icon' => \Filament\Support\Icons\Heroicon::OutlinedStar,
            ],
            [
                'page' => DriverWallet::class,
                'icon' => \Filament\Support\Icons\Heroicon::OutlinedWallet,
            ],
        ];

        $actions = [];

        foreach ($candidates as $candidate) {
            $page = $candidate['page'];

            if (! $page::canAccess()) {
                continue;
            }

            $key = match ($page) {
                ManageShipments::class => 'shipments',
                ManageApprovals::class => 'approvals',
                ManageFinances::class => 'finance',
                ManageReviews::class => 'reviews',
                DriverWallet::class => 'wallet',
            };

            $actions[] = [
                'url' => $page::getUrl(),
                'icon' => $candidate['icon'],
                'title' => __('app.dashboard.quick.' . $key),
                'description' => __('app.dashboard.quick.' . $key . '_description'),
            ];
        }

        return [
            'heading' => __('app.dashboard.quick.heading'),
            'emptyText' => __('app.dashboard.quick.empty'),
            'actions' => $actions,
        ];
    }
}
