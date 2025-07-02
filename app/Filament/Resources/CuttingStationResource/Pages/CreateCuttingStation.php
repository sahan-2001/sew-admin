<?php

namespace App\Filament\Resources\CuttingStationResource\Pages;

use App\Filament\Resources\CuttingStationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCuttingStation extends CreateRecord
{
    protected static string $resource = CuttingStationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
