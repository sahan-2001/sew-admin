<?php

namespace App\Filament\Resources\RegisterArrivalResource\Pages;

use App\Filament\Resources\RegisterArrivalResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PurchaseOrder;

class CreateRegisterArrival extends CreateRecord
{
    protected static string $resource = RegisterArrivalResource::class;

    protected function afterCreate(): void
    {
        // Update the status of the related PurchaseOrder to 'arrived'
        $purchaseOrderId = $this->record->purchase_order_id;
        $purchaseOrder = PurchaseOrder::find($purchaseOrderId);

        if ($purchaseOrder) {
            $purchaseOrder->update(['status' => 'arrived']);
        }
    }
}