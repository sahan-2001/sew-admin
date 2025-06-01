<?php

namespace App\Filament\Resources\CuttingStationResource\Pages;

use App\Filament\Resources\CuttingStationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCuttingStation extends EditRecord
{
    protected static string $resource = CuttingStationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
