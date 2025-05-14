<?php

namespace App\Filament\Resources\MaterialQCResource\Pages;

use App\Filament\Resources\MaterialQCResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialQCS extends ListRecords
{
    protected static string $resource = MaterialQCResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create material qc') && auth()->user()->hasRole('admin')),
        ];
    }
}
