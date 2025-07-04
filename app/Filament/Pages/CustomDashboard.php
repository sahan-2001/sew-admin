<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\StatsOverview;

class CustomDashboard extends BaseDashboard
{
    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
        ];
    }

    public function getColumns(): int
    {
        return 1; // Single column for the dashboard layout
    }

    public function getHeaderWidgetsColumns(): int
    {
        return 1; // Single column for widget container
    }
}