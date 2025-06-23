<?php

namespace App\Filament\Widgets;

use App\Models\Supplier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class SuppliersCount extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Total Suppliers', Supplier::count())
                ->description('All registered suppliers')
                ->descriptionIcon('heroicon-o-truck')
                ->color('danger'),
        ];
    }
}
