<?php

namespace App\Filament\Resources\RequestForQuotationResource\Pages;

use App\Filament\Resources\RequestForQuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;

class CreateRequestForQuotation extends CreateRecord
{
    protected static string $resource = RequestForQuotationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /* ------------------------------
        BASIC DATA
        ------------------------------ */
        $data['random_code'] = strtoupper(Str::random(16));
        $data['status']      = 'draft';

        $items   = $data['items'] ?? [];
        $vatBase = $data['vat_base'] ?? 'item_vat';

        $orderSubtotal = 0;
        $itemVatTotal  = 0;

        /* ------------------------------
        ITEM CALCULATIONS
        ------------------------------ */
        foreach ($items as &$item) {
            $qty   = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $rate  = (float) ($item['inventory_vat_rate'] ?? 0);

            $subTotal = $qty * $price;
            $item['item_subtotal'] = round($subTotal, 2);
            $orderSubtotal += $subTotal;

            if ($vatBase === 'item_vat') {
                $itemVat = ($subTotal * $rate) / 100;
                $item['item_vat_amount']  = round($itemVat, 2);
                $item['item_grand_total'] = round($subTotal + $itemVat, 2);
                $itemVatTotal += $itemVat;
            } else {
                $item['item_vat_amount']  = 0;
                $item['item_grand_total'] = round($subTotal, 2);
            }
        }
        unset($item);

        /* ------------------------------
        ORDER-LEVEL VAT
        ------------------------------ */
        if ($vatBase === 'supplier_vat') {
            $supplierVatRate = (float) ($data['supplier_vat_rate'] ?? 0);
            $vatAmount  = round(($orderSubtotal * $supplierVatRate) / 100, 2);
            $grandTotal = round($orderSubtotal + $vatAmount, 2);
        } else {
            $vatAmount  = 0; // already included in item VAT
            $grandTotal = round($orderSubtotal + $itemVatTotal, 2);
        }

        /* ------------------------------
        PRESERVE & MERGE ALL DATA
        ------------------------------ */
        $data = array_merge($data, [
            'order_subtotal'       => round($orderSubtotal, 2),
            'vat_amount'           => $vatAmount,
            'grand_total'          => $grandTotal,
            'remaining_balance'    => $grandTotal,
            'status'               => $data['status'] ?? 'draft',
            'supplier_vat_group_id' => $data['supplier_vat_group_id'] ?? null, // <-- Ensure it's saved
        ]);

        return $data;
    }

    /* ---------------------------------
     | AFTER CREATE (NOTIFICATIONS)
     |---------------------------------*/
    protected function afterCreate(): void
    {
        $this->record->loadMissing(['items']);
        $this->record->refresh();

        $email = optional($this->record->supplier)->email;

        if (!$email) {
            return;
        }

        try {
            // Example: send notification instead of email if needed
            Notification::make()
                ->title('Purchase Quotation Created')
                ->body("Quotation #{$this->record->id} created successfully and assigned to supplier {$email}")
                ->success()
                ->send();

            // Optional: send email if you have Mailable set up
            // Mail::to($email)->send(new PurchaseQuotationCreatedMail($this->record));

        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
