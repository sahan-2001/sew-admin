<?php

namespace App\Filament\Resources\SupplierAdvanceInvoiceResource\Pages;

use App\Filament\Resources\SupplierAdvanceInvoiceResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\{
    PurchaseOrder,
    SupplierLedgerEntry,
    GeneralLedgerEntry,
    ChartOfAccount,
    SupplierControlAccount,
    SupplierAdvanceInvoice
};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;

class CreateSupplierAdvanceInvoice extends CreateRecord
{
    protected static string $resource = SupplierAdvanceInvoiceResource::class;

    private function createLedgerEntries($invoice, $advanceAmount): void
    {
        $entryCode = 'SUP_ADV_INV_' . now()->format('YmdHis');
        $now = now();
        $userId = Auth::id();

        // Fetch Supplier Control Account + Advance Account
        $supplierControl = SupplierControlAccount::with('supplierAdvanceAccount')
            ->find($invoice->supplier_control_account_id);

        if (!$supplierControl || !$supplierControl->supplier_advance_account_id) {
            throw new \Exception('Supplier advance account not configured.');
        }

        $advanceAccountId = $supplierControl->supplier_advance_account_id;

        // ✅ Fetch the correct Supplier Control Chart of Account
        $supplierControlChart = ChartOfAccount::where('is_control_account', true)
            ->where('control_account_type', 'supplier')
            ->first();

        if (!$supplierControlChart) {
            throw new \Exception('No Supplier Control Chart of Account found.');
        }

        $supplierControlChartId = $supplierControlChart->id;

        try {
            // -----------------------------
            // 1️⃣ Supplier Ledger (Sub Ledger)
            // -----------------------------

            // Debit – Supplier Advance Account
            SupplierLedgerEntry::create([
                'site_id' => $invoice->site_id,
                'entry_code' => $entryCode,
                'supplier_id' => $invoice->supplier_id,
                'chart_of_account_id' => $advanceAccountId,
                'source_table' => 'supplier_advance_invoices account',
                'source_id' => $advanceAccountId,
                'entry_date' => $now,
                'debit' => $advanceAmount,
                'credit' => 0,
                'transaction_name' => 'Supplier Advance Invoice Created',
                'description' => "Advance Invoice Created under supplier advance account ID: {$advanceAccountId}",
                'reference_table' => 'supplier_advance_invoices',
                'reference_record_id' => $invoice->id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Credit – Supplier Control Account
            SupplierLedgerEntry::create([
                'site_id' => $invoice->site_id,
                'entry_code' => $entryCode,
                'supplier_id' => $invoice->supplier_id,
                'chart_of_account_id' => $supplierControlChartId,
                'source_table' => 'supplier_control_accounts',
                'source_id' => $supplierControl->id,
                'entry_date' => $now,
                'debit' => 0,
                'credit' => $advanceAmount,
                'transaction_name' => 'Supplier Advance Invoice Created',
                'description' => "Advance Invoice Created under supplier control account ID: {$supplierControlChartId}",
                'reference_table' => 'supplier_advance_invoices',
                'reference_record_id' => $invoice->id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // -----------------------------
            // 2️⃣ General Ledger (Main Ledger)
            // -----------------------------

            // Debit – Supplier Advance Account
            GeneralLedgerEntry::create([
                'site_id' => $invoice->site_id,
                'entry_code' => $entryCode,
                'account_id' => $advanceAccountId,
                'source_table' => 'supplier_advance_invoices account',
                'source_id' => $advanceAccountId,
                'entry_date' => $now,
                'debit' => $advanceAmount,
                'credit' => 0,
                'transaction_name' => 'Supplier Advance Invoice Created',
                'description' => "Advance Invoice Created under supplier advance account ID: {$advanceAccountId}",
                'reference_table' => 'supplier_advance_invoices',
                'reference_record_id' => $invoice->id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // Credit – Supplier Control Account
            GeneralLedgerEntry::create([
                'site_id' => $invoice->site_id,
                'entry_code' => $entryCode,
                'account_id' => $supplierControlChartId,
                'source_table' => 'supplier_control_accounts',
                'source_id' => $supplierControl->id,
                'entry_date' => $now,
                'debit' => 0,
                'credit' => $advanceAmount,
                'transaction_name' => 'Supplier Advance Invoice Created',
                'description' => "Advance Invoice Created under supplier control account ID: {$supplierControlChartId}",
                'reference_table' => 'supplier_advance_invoices',
                'reference_record_id' => $invoice->id,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // -----------------------------
            // 3️⃣ Update totals
            // -----------------------------
            ChartOfAccount::where('id', $advanceAccountId)->update([
                'debit_total' => \DB::raw("debit_total + $advanceAmount"),
                'debit_total_vat' => \DB::raw("debit_total_vat + $advanceAmount"),
                'balance' => \DB::raw("balance + $advanceAmount"),
                'balance_vat' => \DB::raw("balance_vat + $advanceAmount"),
            ]);

            $supplierControl->update([
                'credit_total' => $supplierControl->credit_total + $advanceAmount,
                'credit_total_vat' => $supplierControl->credit_total_vat + $advanceAmount,
            ]);

        } catch (\Exception $e) {
            \Log::error('Supplier Advance Posting Error', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Ledger Posting Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }


    protected function afterCreate(): void
    {
        $invoice = $this->record;
        $po = PurchaseOrder::find($invoice->purchase_order_id);

        if (!$po) return;

        $advanceAmount = $invoice->payment_type === 'fixed'
            ? $invoice->fix_payment_amount
            : $invoice->percent_calculated_payment;

        if ($advanceAmount > 0) {
            $this->createLedgerEntries($invoice, $advanceAmount);
        }

        $summary = [
            'Invoice ID' => $invoice->id,
            'Supplier' => $invoice->supplier->name ?? '-',
            'PO ID' => $invoice->purchase_order_id,
            'Advance Amount' => 'Rs. ' . number_format($advanceAmount, 2),
            'Supplier Advance Account' => $invoice->supplierAdvanceAccount->name ?? '-',
            'Status' => ucfirst($invoice->status),
        ];

        $message = '<table style="width:100%;border-collapse:collapse;">';
        foreach ($summary as $label => $value) {
            $message .= "<tr>
                <td style='padding:4px;font-weight:bold;border:1px solid #ccc;'>{$label}</td>
                <td style='padding:4px;border:1px solid #ccc;'>{$value}</td>
            </tr>";
        }
        $message .= '</table>';

        Notification::make()
            ->title('Supplier Advance Invoice Summary')
            ->body($message)
            ->success()
            ->send();
    }
}
