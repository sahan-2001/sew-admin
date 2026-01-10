<?php

namespace App\Filament\Resources\DeliveryTermResource\Pages;

use App\Filament\Resources\DeliveryTermResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryTerm extends EditRecord
{
    protected static string $resource = DeliveryTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
