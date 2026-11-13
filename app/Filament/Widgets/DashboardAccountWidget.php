<?php

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget;

class DashboardAccountWidget extends AccountWidget
{
    protected static ?int $sort = 3;

    protected string $view = 'filament.widgets.dashboard-account';
}
