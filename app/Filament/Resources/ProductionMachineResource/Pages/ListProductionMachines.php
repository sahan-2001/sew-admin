<?php

namespace App\Filament\Resources\ProductionMachineResource\Pages;

use App\Filament\Resources\ProductionMachineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductionMachines extends ListRecords
{
    protected static string $resource = ProductionMachineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create production machines')),
        ];
    }
}
