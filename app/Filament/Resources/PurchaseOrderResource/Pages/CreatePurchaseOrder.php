<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use App\Mail\PurchaseOrderCreatedMail;
use Filament\Notifications\Notification;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /* ---------------------------------
         | BASIC DATA
         |---------------------------------*/
        $data['random_code'] = strtoupper(Str::random(16));

        $items   = $data['items'] ?? [];
        $vatBase = $data['vat_base'] ?? 'item_vat';

        $orderSubtotal = 0;
        $itemVatTotal  = 0;

        /* ---------------------------------
         | ITEM CALCULATIONS
         |---------------------------------*/
        foreach ($items as &$item) {
            $qty   = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['price'] ?? 0);
            $rate  = (float) ($item['vat_rate'] ?? 0);

            $subTotal = $qty * $price;
            $item['item_subtotal'] = round($subTotal, 2);

            if ($vatBase === 'item_vat') {
                $itemVat = ($subTotal * $rate) / 100;
                $item['item_vat_amount']  = round($itemVat, 2);
                $item['item_grand_total'] = round($subTotal + $itemVat, 2);
            } else {
                $item['item_vat_amount']  = 0;
                $item['item_grand_total'] = round($subTotal, 2);
            }
        }
        unset($item);

        // Order-level VAT
        if ($vatBase === 'supplier_vat') {
            $supplierVatRate = (float) ($data['supplier_vat_rate'] ?? 0);
            $vatAmount  = round(($orderSubtotal * $supplierVatRate) / 100, 2);
            $grandTotal = round($orderSubtotal + $vatAmount, 2);
        } else {
            $vatAmount  = 0; // do not save item VAT in order
            $grandTotal = $orderSubtotal + $itemVatTotal;
        }

        $discountPercent = (float) ($data['final_discount_percentage'] ?? 0);
        $discountAmount  = round(($grandTotal * $discountPercent) / 100, 2);
        $finalPayable    = round($grandTotal - $discountAmount, 2);

        $data['order_subtotal']    = round($orderSubtotal, 2);
        $data['vat_amount']        = $vatAmount;
        $data['grand_total']       = $grandTotal;

        $data['final_discount_percentage'] = $discountPercent;
        $data['final_discount_amount']     = $discountAmount;
        $data['final_payable_amount']      = max($finalPayable, 0);

        $data['remaining_balance'] = $data['final_payable_amount'];

        return $data;
    }

    /* ---------------------------------
     | AFTER CREATE (EMAIL + QR)
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
            $qrData = url('/purchase-orders/' . $this->record->id . '/' . $this->record->random_code);

            $qrCode  = new QrCode($qrData);
            $writer  = new SvgWriter();
            $result  = $writer->write($qrCode);

            $filename = 'purchase_qrcode_' . $this->record->id . '.svg';
            $path     = 'public/qrcodes/' . $filename;

            Storage::makeDirectory('public/qrcodes');
            Storage::put($path, $result->getString());

            Mail::to($email)->send(new PurchaseOrderCreatedMail($this->record));

            Notification::make()
                ->title('Email Sent Successfully')
                ->body("Purchase order sent to {$email}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Email Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('handle', [
            'record' => $this->record->getKey()
        ]);
    }
}
