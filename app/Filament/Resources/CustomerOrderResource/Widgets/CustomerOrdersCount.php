<?php

namespace App\Filament\Widgets;

use App\Models\CustomerOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class CustomerOrdersCount extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Customer Orders', CustomerOrder::count())
                ->description('Total number of customer orders')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('warning'),
        ];
    }
}
