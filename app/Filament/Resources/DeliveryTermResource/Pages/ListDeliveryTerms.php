<?php

namespace App\Filament\Resources\DeliveryTermResource\Pages;

use App\Filament\Resources\DeliveryTermResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryTerms extends ListRecords
{
    protected static string $resource = DeliveryTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
