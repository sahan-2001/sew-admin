<?php

namespace App\Filament\Resources\RequestForQuotationResource\Pages;

use App\Filament\Resources\RequestForQuotationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
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

        $items = $data['items'] ?? [];

        $orderSubtotal = 0;

        /* ------------------------------
        ITEM CALCULATIONS (NO VAT)
        ------------------------------ */
        foreach ($items as &$item) {
            $qty   = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);

            $subTotal = $qty * $price;

            $item['item_subtotal'] = round($subTotal, 2);
            $orderSubtotal        += $subTotal;
        }
        unset($item);

        /* ------------------------------
        MERGE FINAL DATA
        ------------------------------ */
        $data = array_merge($data, [
            'order_subtotal' => round($orderSubtotal, 2),
            'status'         => 'draft',
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

        if (! $email) {
            return;
        }

        try {
            Notification::make()
                ->title('Request for Quotation Created')
                ->body("RFQ #{$this->record->id} created successfully for supplier {$email}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Notification Failed')
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
