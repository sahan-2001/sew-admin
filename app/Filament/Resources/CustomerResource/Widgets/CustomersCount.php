<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class CustomersCount extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Total Customers', Customer::count())
                ->description('Number of registered customers')
                ->descriptionIcon('heroicon-o-user')
                ->color('info'),
            ];
    }
}
