<?php

namespace App\Filament\Resources\InventoryLocationResource\Pages;

use App\Filament\Resources\InventoryLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventoryLocation extends EditRecord
{
    protected static string $resource = InventoryLocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
