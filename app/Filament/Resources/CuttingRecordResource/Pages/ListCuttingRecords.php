<?php

namespace App\Filament\Resources\CuttingRecordResource\Pages;

use App\Filament\Resources\CuttingRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCuttingRecords extends ListRecords
{
    protected static string $resource = CuttingRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
