<?php

namespace App\Filament\Resources\PurchaseOrderInvoiceResource\Pages;

use App\Filament\Resources\PurchaseOrderInvoiceResource;
use App\Models\PurchaseOrderInvoice;
use App\Models\PurchaseOrderInvoiceItem; 
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePurchaseOrderInvoice extends CreateRecord
{
    protected static string $resource = PurchaseOrderInvoiceResource::class;

    protected function handleRecordCreation(array $data): PurchaseOrderInvoice
    {
        DB::beginTransaction();

        try {
            $invoice = PurchaseOrderInvoice::create([
                'purchase_order_id' => $data['purchase_order_id'],
                'register_arrival_id' => $data['register_arrival_id'],
                'provider_type' => $data['provider_type'] ?? null,
                'provider_id' => $data['provider_id'] ?? null,
                'provider_name' => $data['provider_name'] ?? null,
                'wanted_date' => $data['wanted_date'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Get all item IDs from the invoice items
            $invoiceItemIds = collect($data['invoice_items'] ?? [])->pluck('item_id_i')->unique();

            // Update Register Arrival Items to "invoiced"
            \App\Models\RegisterArrivalItem::where('register_arrival_id', $data['register_arrival_id'])
                ->whereIn('item_id', $invoiceItemIds)
                ->update(['status' => 'invoiced']);

            // Update Material QC records to "invoiced"
            $purchaseOrderId = ltrim($data['purchase_order_id'], '0');
            \App\Models\MaterialQC::where('register_arrival_id', $data['register_arrival_id'])
                ->where('purchase_order_id', $purchaseOrderId)
                ->whereIn('item_id', $invoiceItemIds)
                ->update(['status' => 'invoiced']);


            foreach ($data['invoice_items'] ?? [] as $item) {
            PurchaseOrderInvoiceItem::create([
                'purchase_order_invoice_id' => $invoice->id,
                'item_id' => $item['item_id_i'],
                'stored_quantity' => $item['stored_quantity_i'],
                'location_id' => $item['location_id_i'],
                'unit_price' => $item['price_i'],
            ]);
            }

            DB::commit();
            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
