<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use App\Models\PurchaseOrder;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Number;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRevenue = CustomerOrder::sum('grand_total');

        return [
            Stat::make('Total Users', User::count())
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Total Customers', Customer::count())
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Total Suppliers', Supplier::count())
                ->descriptionIcon('heroicon-o-truck')
                ->color('success'),

            Stat::make('Customer Orders', CustomerOrder::count())
                ->descriptionIcon('heroicon-o-document-text')
                ->color('success'),

            Stat::make('Sample Orders', SampleOrder::count())
                ->descriptionIcon('heroicon-o-beaker')
                ->color('warning'),

            Stat::make('Purchase Orders', PurchaseOrder::count())
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('danger'),

            Stat::make('Total Revenue', Number::currency($totalRevenue))
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('danger'),
        ];
    }

    protected function getColumns(): int
    {
        return 3; // Displays 3 cards per row; adjust as you like
    }
}
