<?php

namespace App\Filament\Resources\RegisterArrivalResource\Pages;

use App\Filament\Resources\RegisterArrivalResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!isset($data['purchase_order_id']) || empty($data['purchase_order_items'])) {
            return $data; // No purchase order or items, skip update
        }

        foreach ($data['purchase_order_items'] as $item) {
            $purchaseOrderItem = PurchaseOrderItem::where('purchase_order_id', $data['purchase_order_id'])
                ->where('inventory_item_id', $item['item_id'])
                ->first();

            if ($purchaseOrderItem) {
                // Update arrived quantity and remaining quantity
                $purchaseOrderItem->arrived_quantity += $item['quantity'];
                $purchaseOrderItem->remaining_quantity -= $item['quantity'];
                $purchaseOrderItem->save();
            }
        }

        return $data;
    }
}