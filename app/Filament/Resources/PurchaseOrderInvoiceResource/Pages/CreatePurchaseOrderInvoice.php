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
            $grandTotal = collect($data['invoice_items'] ?? [])
                ->sum(fn ($item) => floatval($item['total'] ?? 0));
            
            $totalPaidAmount = collect($data['supplier_advance_invoices'] ?? [])
                ->sum(fn ($item) => floatval($item['paid_amount'] ?? 0));

            $invoice = PurchaseOrderInvoice::create([
                'purchase_order_id' => $data['purchase_order_id'],
                'register_arrival_id' => $data['register_arrival_id'],
                'provider_type' => $data['provider_type'] ?? null,
                'provider_id' => $data['provider_id'] ?? null,
                'provider_name' => $data['provider_name'] ?? null,
                'wanted_date' => $data['wanted_date'] ?? null,
                'grand_total' => $grandTotal, 
                'total_paid_amount' => $totalPaidAmount, 
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

            // Create invoice items
            foreach ($data['invoice_items'] ?? [] as $item) {
                PurchaseOrderInvoiceItem::create([
                    'purchase_order_invoice_id' => $invoice->id,
                    'item_id' => $item['item_id_i'],
                    'stored_quantity' => $item['stored_quantity_i'],
                    'location_id' => $item['location_id_i'],
                    'unit_price' => $item['price_i'],
                ]);
            }
            
            // Create supplier advance invoice deductions
            foreach ($data['supplier_advance_invoices'] ?? [] as $advInvoice) {
                \App\Models\PoAdvInvDeduct::create([
                    'purchase_order_invoice_id' => $invoice->id,
                    'advance_invoice_id' => $advInvoice['id'],
                    'deduction_amount' => $advInvoice['paid_amount'],
                ]);
            }

            // Create additional costs
            foreach ($data['additional_costs'] ?? [] as $cost) {
                \App\Models\PoAddCost::create([
                    'purchase_order_invoice_id' => $invoice->id,
                    'description' => $cost['description_c'],
                    'unit_rate' => $cost['unit_rate_c'],
                    'quantity' => $cost['quantity_c'],
                    'uom' => $cost['uom_c'],
                    'total' => $cost['total_c'],
                    'date' => $cost['date_c'],
                    'remarks' => $cost['remarks_c'] ?? null,
                ]);
            }

            // Create discounts and deductions
            foreach ($data['discounts_deductions'] ?? [] as $discount) {
                \App\Models\PurchaseOrderDiscount::create([
                    'purchase_order_invoice_id' => $invoice->id,
                    'description' => $discount['description_d'],
                    'unit_rate' => $discount['unit_rate_d'],
                    'quantity' => $discount['quantity_d'],
                    'uom' => $discount['uom_d'],
                    'total' => $discount['total_d'],
                    'date' => $discount['date_d'],
                    'remarks' => $discount['remarks_d'] ?? null,
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