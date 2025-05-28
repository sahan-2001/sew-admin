<?php

namespace App\Filament\Resources\StockOverviewResource\Pages;

use App\Filament\Resources\StockOverviewResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;


class ListStockOverview extends ListRecords
{
    protected static string $resource = StockOverviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()        ];
    }
}
