<?php

namespace App\Filament\Resources\ProductionMachineResource\Pages;

use App\Filament\Resources\ProductionMachineResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductionMachine extends CreateRecord
{
    protected static string $resource = ProductionMachineResource::class;

    protected function beforeSave()
    {
        $this->record->total_initial_cost = $this->record->purchased_cost + ($this->record->additional_cost ?? 0);
        $this->record->calculateDepreciation();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
