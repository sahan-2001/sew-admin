<?php

namespace App\Filament\Resources\InventoryLocationResource\Pages;

use App\Filament\Resources\InventoryLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryLocation extends CreateRecord
{
    protected static string $resource = InventoryLocationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * After a new Inventory Location is created,
     * update the related warehouse status to 'active'
     */
    protected function afterCreate(): void
    {
        // Ensure the record exists
        if ($this->record && $this->record->warehouse_id) {

            \App\Models\Warehouse::where('id', $this->record->warehouse_id)
                ->update(['status' => 'active']);
        }
    }
}
