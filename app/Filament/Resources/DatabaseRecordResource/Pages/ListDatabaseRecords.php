<?php

namespace App\Filament\Resources\DatabaseRecordResource\Pages;

use App\Filament\Resources\DatabaseRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDatabaseRecords extends ListRecords
{
    protected static string $resource = DatabaseRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
