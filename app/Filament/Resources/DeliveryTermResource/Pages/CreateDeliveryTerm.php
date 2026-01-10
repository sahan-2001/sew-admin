<?php

namespace App\Filament\Resources\DeliveryTermResource\Pages;

use App\Filament\Resources\DeliveryTermResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDeliveryTerm extends CreateRecord
{
    protected static string $resource = DeliveryTermResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
