<?php

namespace App\Filament\Resources\StockOverviewResource\Pages;

use App\Filament\Resources\StockOverviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockOverview extends EditRecord
{
    protected static string $resource = StockOverviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
