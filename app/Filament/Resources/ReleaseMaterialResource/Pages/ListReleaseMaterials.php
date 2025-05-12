<?php

namespace App\Filament\Resources\ReleaseMaterialResource\Pages;

use App\Filament\Resources\ReleaseMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReleaseMaterials extends ListRecords
{
    protected static string $resource = ReleaseMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create Release Materials')),
        ];
    }
}
