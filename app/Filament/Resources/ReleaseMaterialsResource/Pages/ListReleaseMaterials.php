<?php

namespace App\Filament\Resources\ReleaseMaterialsResource\Pages;

use App\Filament\Resources\ReleaseMaterialsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReleaseMaterials extends ListRecords
{
    protected static string $resource = ReleaseMaterialsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
