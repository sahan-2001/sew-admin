<?php

namespace App\Filament\Resources\ProductionLineOperationResource\Pages;

use App\Filament\Resources\ProductionLineOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductionLineOperations extends ListRecords
{
    protected static string $resource = ProductionLineOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create workstations')),
        ];
    }
}
