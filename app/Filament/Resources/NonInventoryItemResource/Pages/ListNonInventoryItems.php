<?php

namespace App\Filament\Resources\NonInventoryItemResource\Pages;

use App\Filament\Resources\NonInventoryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNonInventoryItems extends ListRecords
{
    protected static string $resource = NonInventoryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
