<?php

namespace App\Filament\Resources\RegisterArrivalItemResource\Pages;

use App\Filament\Resources\RegisterArrivalItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRegisterArrivalItems extends ListRecords
{
    protected static string $resource = RegisterArrivalItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
