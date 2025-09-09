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
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $startDate = Carbon::now()->subDays(30);

        // Totals
        $totalCustomerRevenue  = CustomerOrder::where('created_at', '>=', $startDate)->sum('grand_total');
        $customerOrders30Days  = CustomerOrder::where('created_at', '>=', $startDate)->count();
        $purchaseRevenue30Days = PurchaseOrder::where('created_at', '>=', $startDate)->sum('grand_total');

        $sampleOrders30Days    = SampleOrder::where('created_at', '>=', $startDate)->count();
        $sampleRevenue30Days   = SampleOrder::where('created_at', '>=', $startDate)->sum('grand_total');

        $purchaseOrders30Days  = PurchaseOrder::where('created_at', '>=', $startDate)->count();

        return [
            Stat::make('👥 Total Users', User::count())
                ->description('Active user accounts')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary')
                ->chart([10, 20, 30, 40, 50, 40, 30]),

            Stat::make('👔 Total Customers', Customer::count())
                ->description('Registered customers')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('info')
                ->chart([12, 18, 20, 24, 26, 30, 34]),

            Stat::make('🚚 Total Suppliers', Supplier::count())
                ->description('Vendors currently working')
                ->descriptionIcon('heroicon-o-truck')
                ->color('success')
                ->chart([5, 8, 12, 15, 13, 17, 20]),

            Stat::make('📦 Customer Orders (30 Days)', $customerOrders30Days)
                ->description('Customer orders created last 30 days')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->color('success'),

            Stat::make('🧪 Sample Orders (30 Days)', $sampleOrders30Days)
                ->description('Sample orders created last 30 days')
                ->descriptionIcon('heroicon-o-beaker')
                ->color('warning'),

            Stat::make('🛒 Purchase Orders (30 Days)', $purchaseOrders30Days)
                ->description('Purchase orders created last 30 days')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('danger'),

            Stat::make('💰 Total Customer Revenue (30 Days)', $this->formatCurrency($totalCustomerRevenue, 'LKR'))
                ->description('From customer orders in last 30 days')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('danger'),

            Stat::make('🧾 Sample Revenue (30 Days)', $this->formatCurrency($sampleRevenue30Days, 'LKR'))
                ->description('From sample orders in last 30 days')
                ->descriptionIcon('heroicon-o-receipt-refund')
                ->color('warning'),

            Stat::make('🛒 Purchase Order Cost (30 Days)', $this->formatCurrency($purchaseRevenue30Days, 'LKR'))
                ->description('From purchase orders in last 30 days')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('danger'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    /**
     * Simple currency formatter that works without intl
     */
    private function formatCurrency(float|int $amount, string $currency = 'USD'): string
    {
        $symbols = [
            'LKR' => 'Rs.',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'JPY' => '¥',
        ];

        $symbol = $symbols[$currency] ?? $currency;
        return $symbol . ' ' . number_format($amount, 2);
    }
}
