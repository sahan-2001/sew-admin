<?php

namespace App\Filament\Resources\RegisterArrivalResource\Pages;

use App\Filament\Resources\RegisterArrivalResource;
use Filament\Resources\Pages\EditRecord;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;

class EditRegisterArrival extends EditRecord
{
    protected static string $resource = RegisterArrivalResource::class;

    protected function afterSave(): void
    {
        $registerArrival = $this->record;

        // Update PurchaseOrder and PurchaseOrderItem quantities
        RegisterArrivalResource::updatePurchaseOrderStatusAndItems($registerArrival);
    }

    protected function getHeaderActions(): array
    {
        return [
        ];

    }
}
