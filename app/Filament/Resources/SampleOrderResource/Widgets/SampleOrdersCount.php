<?php

namespace App\Filament\Widgets;

use App\Models\SampleOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class SampleOrdersCount extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Sample Orders', SampleOrder::count())
                ->description('Total sample orders received')
                ->descriptionIcon('heroicon-o-clipboard-document')
                ->color('primary'),
        ];
    }
}
