<?php

namespace App\Filament\Resources\PurchaseOrderInvoiceResource\Pages;

use App\Filament\Resources\PurchaseOrderInvoiceResource;
use App\Models\PurchaseOrderInvoice;
use App\Models\PurchaseOrderInvoiceItem; 
use App\Models\PurchaseOrder;
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
            
            $totalPaidAmount = collect($data['advance_invoices'] ?? [])
                ->sum(fn ($item) => floatval($item['paid_amount'] ?? 0));

            $totalAdditionalCost = collect($data['additional_costs'] ?? [])
                ->sum(fn ($item) => floatval($item['total_c'] ?? 0));

            $totalDiscountsDeductions = collect($data['discounts_deductions'] ?? [])
                ->sum(fn ($item) => floatval($item['total_d'] ?? 0));

            $paymentDue = 
                collect($data['invoice_items'] ?? [])
                    ->sum(fn ($item) => floatval($item['total'] ?? 0))
                + collect($data['additional_costs'] ?? [])
                    ->sum(fn ($item) => floatval($item['total_c'] ?? 0))
                - collect($data['advance_invoices'] ?? [])
                    ->sum(fn ($item) => floatval($item['paid_amount'] ?? 0))
                - collect($data['discounts_deductions'] ?? [])
                    ->sum(fn ($item) => floatval($item['total_d'] ?? 0));

            $invoice = PurchaseOrderInvoice::create([
                'purchase_order_id' => $data['purchase_order_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'wanted_date' => $data['wanted_date'] ?? null,
                'grand_total' => $grandTotal, 
                'adv_paid' => $totalPaidAmount, 
                'additional_cost' => $totalAdditionalCost,
                'discount' => $totalDiscountsDeductions,
                'total_calculation_method' => $data['total_calculation_method'] ?? 'manual', // default value
                'due_payment' => $paymentDue,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            
            // Get the purchase order ID (remove leading zeros)
            $purchaseOrderId = ltrim($data['purchase_order_id'], '0');
            
            // Update Purchase Order status to 'invoiced'
            PurchaseOrder::where('id', $purchaseOrderId)
                ->update(['status' => 'invoiced']);
                
            // Get all register arrival IDs from the invoice items
            $registerArrivalIds = collect($data['invoice_items'] ?? [])
                ->pluck('register_arrival_id')
                ->unique()
                ->filter()
                ->values()
                ->toArray();

            // Get all item IDs from the invoice items
            $invoiceItemIds = collect($data['invoice_items'] ?? [])
                ->pluck('item_id_i')
                ->unique()
                ->filter()
                ->values()
                ->toArray();

            // Update Register Arrival Items to "invoiced" for all relevant register arrivals
            if (!empty($registerArrivalIds) && !empty($invoiceItemIds)) {
                \App\Models\RegisterArrivalItem::whereIn('register_arrival_id', $registerArrivalIds)
                    ->whereIn('item_id', $invoiceItemIds)
                    ->update(['status' => 'invoiced']);
            }

            // Update Material QC records to "invoiced"
            $purchaseOrderId = ltrim($data['purchase_order_id'], '0');
            if (!empty($registerArrivalIds)) {
                \App\Models\MaterialQC::whereIn('register_arrival_id', $registerArrivalIds)
                    ->where('purchase_order_id', $purchaseOrderId)
                    ->whereIn('item_id', $invoiceItemIds)
                    ->update(['status' => 'invoiced']);
            }

            // Create invoice items
            foreach ($data['invoice_items'] ?? [] as $item) {
                PurchaseOrderInvoiceItem::create([
                    'purchase_order_invoice_id' => $invoice->id,
                    'register_arrival_id' => $item['register_arrival_id'],
                    'item_id' => $item['item_id_i'],
                    'stored_quantity' => $item['stored_quantity_i'],
                    'location_id' => $item['location_id_i'],
                    'unit_price' => $item['price_i'],
                ]);
            }
            
            // Create supplier advance invoice deductions and update their status
            foreach ($data['advance_invoices'] ?? [] as $advInvoice) {
                \App\Models\PoAdvInvDeduct::create([
                    'purchase_order_invoice_id' => $invoice->id,
                    'advance_invoice_id' => $advInvoice['id'],
                    'deduction_amount' => $advInvoice['paid_amount'],
                ]);
                
                // Update the SupplierAdvanceInvoice status to 'deducted'
                \App\Models\SupplierAdvanceInvoice::where('id', $advInvoice['id'])
                    ->update(['status' => 'deducted']);
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