<?php
namespace App\Filament\Resources\SupplierAdvanceInvoiceResource\Pages;

use App\Filament\Resources\SupplierAdvanceInvoiceResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PurchaseOrder;

class CreateSupplierAdvanceInvoice extends CreateRecord
{
    protected static string $resource = SupplierAdvanceInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['grand_total'] = collect($data['purchase_order_items'] ?? [])->sum('total_sale');
        
        // Ensure provider_type and provider_id are set
        if (empty($data['provider_type'])) {
            $purchaseOrder = PurchaseOrder::find($data['purchase_order_id']);
            $data['provider_type'] = $purchaseOrder->provider_type ?? null;
            $data['provider_id'] = $purchaseOrder->provider_id ?? null;
        }
        
        return $data;
    }
}