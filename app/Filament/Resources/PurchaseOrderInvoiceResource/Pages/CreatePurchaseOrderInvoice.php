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

    private function createLedgerEntries(PurchaseOrderInvoice $invoice): void
    {
        $entryCode = 'PO_INV_' . now()->format('YmdHis');
        $now = now();
        $userId = auth()->id();

        $supplierControl = \App\Models\SupplierControlAccount::find(
            $invoice->supplier_control_account_id
        );

        if (!$supplierControl) {
            throw new \Exception('Supplier control account not configured.');
        }

        /**
         * ===============================
         * 1️⃣ PURCHASE INVOICE
         * ===============================
         */

        // Supplier Ledger
        \App\Models\SupplierLedgerEntry::create([
            'entry_code' => $entryCode,
            'supplier_id' => null,
            'chart_of_account_id' => $invoice->purchase_account_id,
            'entry_date' => $now,
            'debit' => $invoice->grand_total,
            'credit' => 0,
            'transaction_name' => 'Purchase Invoice',
            'description' => "Purchase Invoice Liability/ Invoice ID: {$invoice->id}",
            'invoice_id' => $invoice->id,
            'purchase_order_id' => $invoice->purchase_order_id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        \App\Models\SupplierLedgerEntry::create([
            'entry_code' => $entryCode,
            'supplier_id' => null,
            'chart_of_account_id' => $invoice->purchase_account_id,
            'entry_date' => $now,
            'debit' => $invoice->adv_paid,
            'credit' => 0,
            'transaction_name' => 'Purchase Invoice',
            'description' => "Deduction of paid advance invoices/ Invoice ID: {$invoice->id}",
            'invoice_id' => $invoice->id,
            'purchase_order_id' => $invoice->purchase_order_id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
        
        \App\Models\SupplierLedgerEntry::create([
            'entry_code' => $entryCode,
            'supplier_id' => $invoice->supplier_id,
            'chart_of_account_id' => null,
            'entry_date' => $now,
            'debit' => 0,
            'credit' => $invoice->due_payment,
            'transaction_name' => 'Purchase Invoice',
            'description' => "Purchase Invoice (Arrived item cost of purchase order)/ Invoice ID: {$invoice->id}",
            'invoice_id' => $invoice->id,
            'purchase_order_id' => $invoice->purchase_order_id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // General Ledger
        GeneralLedgerEntry::create([
            'entry_code' => $entryCode,
            'account_id' => $invoice->purchase_account_id,
            'entry_date' => $now,
            'debit' => $invoice->grand_total,
            'credit' => 0,
            'transaction_name' => 'Purchase Invoice',
            'description' => "Deduction of paid advance invoices/ Invoice ID: {$invoice->id}",
            'source_table' => 'purchase_order_invoices',
            'source_id' => $invoice->id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        GeneralLedgerEntry::create([
            'entry_code' => $entryCode,
            'account_id' => $invoice->purchase_account_id,
            'entry_date' => $now,
            'debit' => $invoice->grand_total,
            'credit' => 0,
            'transaction_name' => 'Purchase Invoice',
            'description' => "Deduction of paid advance invoices/ Invoice ID: {$invoice->id}",
            'source_table' => 'purchase_order_invoices',
            'source_id' => $invoice->id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        GeneralLedgerEntry::create([
            'entry_code' => $entryCode,
            'account_id' => null,
            'Control_account_table' => 'supplier_control_accounts',
            'control_account_record_id' => $supplierControl->id,
            'entry_date' => $now,
            'debit' => 0,
            'credit' => $invoice->grand_total,
            'transaction_name' => 'Purchase Invoice',
            'source_table' => 'purchase_order_invoices',
            'source_id' => $invoice->id,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        // Update totals
        ChartOfAccount::where('id', $invoice->purchase_account_id)->update([
            'debit_total' => DB::raw("debit_total + {$invoice->grand_total}"),
            'debit_total_vat' => DB::raw("debit_total_vat + {$invoice->grand_total}"),
        ]);

        $supplierControl->increment('credit_total', $invoice->grand_total);
        $supplierControl->increment('credit_total_vat', $invoice->grand_total);

        /**
         * ===============================
         * 2️⃣ FREIGHT / ADDITIONAL COST
         * ===============================
         */
        if ($invoice->additional_cost > 0 && $invoice->freight_in_account_id) {

            GeneralLedgerEntry::create([
                'entry_code' => $entryCode,
                'account_id' => $invoice->freight_in_account_id,
                'entry_date' => $now,
                'debit' => $invoice->additional_cost,
                'credit' => 0,
                'transaction_name' => 'Freight In',
                'source_table' => 'purchase_order_invoices',
                'source_id' => $invoice->id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            GeneralLedgerEntry::create([
                'entry_code' => $entryCode,
                'account_id' => null,
                'Control_account_table' => 'supplier_control_accounts',
                'control_account_record_id' => $supplierControl->id,
                'entry_date' => $now,
                'debit' => 0,
                'credit' => $invoice->additional_cost,
                'transaction_name' => 'Freight In',
                'source_table' => 'purchase_order_invoices',
                'source_id' => $invoice->id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            ChartOfAccount::where('id', $invoice->freight_in_account_id)->increment(
                'debit_total',
                $invoice->additional_cost
            );

            $supplierControl->increment('credit_total', $invoice->additional_cost);
        }

        /**
         * ===============================
         * 3️⃣ DISCOUNTS / DEDUCTIONS
         * ===============================
         */
        if ($invoice->discount > 0 && $invoice->purchase_discount_account_id) {

            GeneralLedgerEntry::create([
                'entry_code' => $entryCode,
                'account_id' => null,
                'Control_account_table' => 'supplier_control_accounts',
                'control_account_record_id' => $supplierControl->id,
                'entry_date' => $now,
                'debit' => $invoice->discount,
                'credit' => 0,
                'transaction_name' => 'Purchase Discount',
                'source_table' => 'purchase_order_invoices',
                'source_id' => $invoice->id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            GeneralLedgerEntry::create([
                'entry_code' => $entryCode,
                'account_id' => $invoice->purchase_discount_account_id,
                'entry_date' => $now,
                'debit' => 0,
                'credit' => $invoice->discount,
                'transaction_name' => 'Purchase Discount',
                'source_table' => 'purchase_order_invoices',
                'source_id' => $invoice->id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            ChartOfAccount::where('id', $invoice->purchase_discount_account_id)->increment(
                'credit_total',
                $invoice->discount
            );

            $supplierControl->decrement('credit_total', $invoice->discount);
        }
    }


}