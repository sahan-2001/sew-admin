<?php

namespace App\Filament\Resources\TemporaryOperationResource\Pages;

use App\Filament\Resources\TemporaryOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTemporaryOperations extends ListRecords
{
    protected static string $resource = TemporaryOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
