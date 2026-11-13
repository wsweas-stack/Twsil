<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    protected static ?int $navigationSort = -2;

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('app.dashboard.title');
    }

    public static function getNavigationLabel(): string|Htmlable
    {
        return __('app.dashboard.title');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('app.dashboard.subtitle');
    }
}
