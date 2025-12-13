<?php
namespace App\Filament\Resources\SupplierAdvanceInvoiceResource\Pages;

use App\Filament\Resources\SupplierAdvanceInvoiceResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\PurchaseOrder;
use App\Models\SupplierLedgerEntry;
use App\Models\GeneralLedgerEntry;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;
use App\Models\SupplierControlAccount;

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

        if ($supplierId) {
            $supplierControl = SupplierControlAccount::where('supplier_id', $supplierId)->first();

            if (!$supplierControl?->supplier_advance_account_id) {
                Notification::make()
                    ->title('Supplier Account Not Configured')
                    ->body("The Supplier Control Account does not have a Supplier Advance Account configured. Please go to the customer control account's edit page & set it before creating an invoice.")
                    ->danger()
                    ->send();

                throw ValidationException::withMessages([
                    'supplier_id' => 'Supplier advance account is not configured in Supplier Control Account.',
                ]);
            }
        }

        // Proceed with your existing calculation logic
        $data['grand_total'] = collect($data['purchase_order_items'] ?? [])->sum('total_sale');

        if (empty($data['provider_type'])) {
            $data['provider_type'] = $po->provider_type ?? null;
            $data['provider_id'] = $po->provider_id ?? null;
        }

        if (($data['payment_type'] ?? null) === 'fixed') {
            $data['paid_amount'] = (float) ($data['fix_payment_amount'] ?? 0);
        } elseif (($data['payment_type'] ?? null) === 'percentage') {
            $remaining = (float) ($data['remaining_balance'] ?? 0);
            $percentage = (float) ($data['payment_percentage'] ?? 0);
            $data['paid_amount'] = $remaining * ($percentage / 100);
        } else {
            $data['paid_amount'] = 0;
        }

        $grandTotal = (float) $data['grand_total'];
        $data['remaining_amount'] = $grandTotal - $data['paid_amount'];
        $data['status'] = $data['remaining_amount'] <= 0 ? 'paid' : 'partially_paid';

        return $data;
    }

}
