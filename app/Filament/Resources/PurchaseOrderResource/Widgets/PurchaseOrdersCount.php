<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class PurchaseOrdersCount extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Purchase Orders', PurchaseOrder::count())
                ->description('Total number of purchase orders')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->color('success'),
        ];
    }
}
