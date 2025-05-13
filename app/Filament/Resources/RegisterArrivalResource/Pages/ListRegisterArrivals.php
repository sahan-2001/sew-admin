<?php

namespace App\Filament\Resources\RegisterArrivalResource\Pages;

use App\Filament\Resources\RegisterArrivalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRegisterArrivals extends ListRecords
{
    protected static string $resource = RegisterArrivalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create register arrivals')),
        ];
    }
}
