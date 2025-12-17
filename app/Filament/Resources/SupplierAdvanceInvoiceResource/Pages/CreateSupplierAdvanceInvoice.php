<?php

namespace App\Filament\Resources\SupplierAdvanceInvoiceResource\Pages;

use App\Filament\Resources\SupplierAdvanceInvoiceResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\{
    PurchaseOrder,
    SupplierLedgerEntry,
    ChartOfAccount,
    Supplier,
    SupplierControlAccount
};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;

class CreateSupplierAdvanceInvoice extends CreateRecord
{
    protected static string $resource = SupplierAdvanceInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $po = PurchaseOrder::find($data['purchase_order_id']);
        if (!$po) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'Selected Purchase Order not found.',
            ]);
        }

        $supplierId = $po->provider_type === 'supplier' ? $po->provider_id : null;

        // Fetch supplier control account
        $supplierControl = null;
        if ($supplierId) {
            $supplierControl = SupplierControlAccount::with('supplierAdvanceAccount')
                ->where('supplier_id', $supplierId)
                ->first();

            if (!$supplierControl?->supplier_advance_account_id) {
                Notification::make()
                    ->title('Supplier Account Not Configured')
                    ->body("Supplier Advance Account is not configured. Please configure it in the Supplier Control Account before creating the invoice.")
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'supplier_id' => 'Supplier advance account not configured.',
                ]);
            }
        }

        // Calculate grand total
        $data['grand_total'] = collect($data['purchase_order_items'] ?? [])->sum('total_sale');

        // Set provider type/id if not provided
        $data['provider_type'] ??= $po->provider_type;
        $data['provider_id'] ??= $po->provider_id;

        // Calculate paid amount
        if (($data['payment_type'] ?? null) === 'fixed') {
            $data['paid_amount'] = (float) ($data['fix_payment_amount'] ?? 0);
        } elseif (($data['payment_type'] ?? null) === 'percentage') {
            $remaining = (float) ($data['remaining_balance'] ?? 0);
            $percentage = (float) ($data['payment_percentage'] ?? 0);
            $data['paid_amount'] = $remaining * ($percentage / 100);
        } else {
            $data['paid_amount'] = 0;
        }

        // Remaining balance & status
        $data['remaining_amount'] = max($data['grand_total'] - $data['paid_amount'], 0);
        $data['status'] = $data['remaining_amount'] <= 0 ? 'paid' : 'partially_paid';

        // Store control & advance account IDs
        $data['supplier_control_account_id'] = $supplierControl->id ?? null;
        $data['supplier_advance_account_id'] = $supplierControl->supplier_advance_account_id ?? null;

        return $data;
    }

    protected function afterCreate(): void
    {
        $invoice = $this->record;
        $paidAmount = $invoice->paid_amount;

        if ($paidAmount <= 0) {
            return; // No payment to process
        }

        $entryCode = 'SUP-ADV-' . strtoupper(uniqid());

        $supplier = Supplier::where('supplier_id', $invoice->supplier_id)->first();
        if (!$supplier) {
            Notification::make()
                ->title('Supplier Not Found')
                ->body('Supplier not found. Ledger entry creation skipped.')
                ->warning()
                ->send();
            return;
        }

        // 1. Debit Supplier Account
        SupplierLedgerEntry::create([
            'entry_code' => 21,
            'supplier_id' => $invoice->supplier_id,
            'chart_of_account_id' => $supplier->default_ledger_account_id,
            'entry_date' => now(),
            'debit' => $paidAmount,
            'credit' => 0,
            'transaction_name' => 'Supplier Advance Payment',
            'description' => "Supplier ID: {$invoice->supplier_id} | Advance Invoice ID: {$invoice->id} | PO ID: {$invoice->purchase_order_id}",
        ]);

        // 2. Credit Supplier Advance Account
        SupplierLedgerEntry::create([
            'entry_code' => 21,
            'supplier_id' => $invoice->supplier_id,
            'chart_of_account_id' => $invoice->supplier_advance_account_id,
            'entry_date' => now(),
            'debit' => 0,
            'credit' => $paidAmount,
            'transaction_name' => 'Supplier Advance Payment',
            'description' => "Advance Account | Supplier ID: {$invoice->supplier_id} | Advance Invoice ID: {$invoice->id} | PO ID: {$invoice->purchase_order_id}",
        ]);

        // 3. Update Supplier Control Account & Advance Account balances
        if ($invoice->supplier_control_account_id) {
            $supplierControlAccount = SupplierControlAccount::find($invoice->supplier_control_account_id);
            $advanceAccount = optional(ChartOfAccount::find($invoice->supplier_advance_account_id));

            if ($supplierControlAccount) {
                $supplierControlAccount->increment('debit_total', $paidAmount);
                $supplierControlAccount->increment('balance', $paidAmount);
                $supplierControlAccount->update(['updated_by' => Auth::id()]);
            }

            if ($advanceAccount) {
                $advanceAccount->decrement('balance', $paidAmount);
                $advanceAccount->update(['updated_by' => Auth::id()]);
            }
        }
    }
}
