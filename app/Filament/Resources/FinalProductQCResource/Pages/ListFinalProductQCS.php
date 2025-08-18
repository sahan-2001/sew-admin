<?php

namespace App\Filament\Resources\FinalProductQCResource\Pages;

use App\Filament\Resources\FinalProductQCResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinalProductQCS extends ListRecords
{
    protected static string $resource = FinalProductQCResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
