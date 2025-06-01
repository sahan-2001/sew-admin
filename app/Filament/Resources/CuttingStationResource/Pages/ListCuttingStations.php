<?php

namespace App\Filament\Resources\CuttingStationResource\Pages;

use App\Filament\Resources\CuttingStationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCuttingStations extends ListRecords
{
    protected static string $resource = CuttingStationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
