<?php
namespace App\Filament\Resources\SupplierAdvanceInvoiceResource\Pages;

use App\Filament\Resources\SupplierAdvanceInvoiceResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PurchaseOrder;
use App\Models\SupplierLedgerEntry;
use App\Models\GeneralLedgerEntry;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\Auth;

class CreateSupplierAdvanceInvoice extends CreateRecord
{
    protected static string $resource = SupplierAdvanceInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['grand_total'] = collect($data['purchase_order_items'] ?? [])->sum('total_sale');

        if (empty($data['provider_type'])) {
            $po = PurchaseOrder::find($data['purchase_order_id']);
            $data['provider_type'] = $po->provider_type ?? null;
            $data['provider_id'] = $po->provider_id ?? null;
        }

        // Calculate the final paid amount from form inputs
        if (($data['payment_type'] ?? null) === 'fixed') {
            $data['paid_amount'] = (float) ($data['fix_payment_amount'] ?? 0);
        } elseif (($data['payment_type'] ?? null) === 'percentage') {
            $remaining = (float) ($data['remaining_balance'] ?? 0);
            $percentage = (float) ($data['payment_percentage'] ?? 0);
            $data['paid_amount'] = $remaining * ($percentage / 100);
        } else {
            $data['paid_amount'] = 0;
        }

        // Compute remaining amount
        $grandTotal = (float) $data['grand_total'];
        $data['remaining_amount'] = $grandTotal - $data['paid_amount'];

        // Set initial status
        $data['status'] = $data['remaining_amount'] <= 0 ? 'paid' : 'partially_paid';

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record->fresh();
        $paymentAmount = (float) ($record->paid_amount ?? 0);

        if ($paymentAmount <= 0 || $record->provider_type !== 'supplier') {
            return;
        }

        // Supplier Ledger Entry
        \App\Models\SupplierLedgerEntry::create([
            'entry_code' => 'SUP_ADV_PAY_' . $record->id . '_' . now()->timestamp,
            'supplier_id' => $record->provider_id,
            'entry_date' => now(),
            'debit' => 0,
            'credit' => $paymentAmount,
            'transaction_name' => 'Supplier Advance Payment',
            'description' => 'Payment for Supplier Advance Invoice ID: ' . $record->id,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
            'purchase_order_id' => $record->purchase_order_id,
            'invoice_id' => $record->id,
        ]);

        // Supplier Advance Account in GL
        $supplierControl = \App\Models\SupplierControlAccount::where('supplier_id', $record->provider_id)->first();
        if ($supplierControl?->supplier_advance_account_id) {
            \App\Models\GeneralLedgerEntry::create([
                'entry_code' => 'GL_SUP_ADV_PAY_' . $record->id . '_' . now()->timestamp,
                'account_id' => $supplierControl->supplier_advance_account_id,
                'entry_date' => now(),
                'debit' => 0,
                'credit' => $paymentAmount,
                'transaction_name' => 'Supplier Advance Payment',
                'description' => 'Payment recorded for Supplier Advance Invoice ID: ' . $record->id,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }
    }

}
