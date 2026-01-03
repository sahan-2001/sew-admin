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
        // Generate random code
        $data['random_code'] = strtoupper(\Illuminate\Support\Str::random(16));

        // Calculate totals from items
        $items = $data['items'] ?? [];

        $orderSubtotal = 0;
        $vatAmount     = 0;
        $grandTotal    = 0;

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $price    = (float) ($item['price'] ?? 0);
            $vatRate  = (float) ($item['vat_rate'] ?? 0);

            $subTotal = $quantity * $price;
            $vat      = ($subTotal * $vatRate) / 100;
            $total    = $subTotal + $vat;

            $orderSubtotal += $subTotal;
            $vatAmount     += $vat;
            $grandTotal    += $total;

            // Ensure hidden fields for each item are set for DB
            $item['item_subtotal']     = round($subTotal, 2);
            $item['item_vat_amount']   = round($vat, 2);
            $item['item_grand_total']  = round($total, 2);

            $data['items'][] = $item; // update back
        }

        // Assign calculated totals to main order
        $data['order_subtotal']    = round($orderSubtotal, 2);
        $data['vat_amount']        = round($vatAmount, 2);
        $data['grand_total']       = round($grandTotal, 2);

        // remaining_balance defaults to grand_total
        $data['remaining_balance'] = $data['grand_total'];

        // Default VAT base
        $data['vat_base'] = $data['vat_base'] ?? 'item_vat';

        return $data;
    }


    protected function afterCreate(): void
    {
        $this->record->loadMissing(['items', 'invoice', 'supplierAdvanceInvoices']);
        $this->record->refresh();

        $email = null;

        // Get supplier email
        if ($this->record->supplier_id) {
            $supplier = \App\Models\Supplier::find($this->record->supplier_id);
            if ($supplier && $supplier->email) {
                $email = $supplier->email;
            }
        }

        if ($email) {
            try {
                // Generate QR code URL
                $qrCodeData = url('/purchase-orders/' . $this->record->id . '/' . $this->record->random_code);

                // Generate & store SVG QR code
                $qrCode = new QrCode($qrCodeData);
                $writer = new SvgWriter();
                $result = $writer->write($qrCode);

                $qrCodeFilename = 'purchase_qrcode_' . $this->record->id . '.svg';
                $path = 'public/qrcodes/' . $qrCodeFilename;

                Storage::makeDirectory('public/qrcodes');
                Storage::put($path, $result->getString());

                // Send email
                Mail::to($email)->send(new PurchaseOrderCreatedMail($this->record));

                // Notify Filament user of success
                Notification::make()
                    ->title('Email Sent Successfully')
                    ->body("Purchase order confirmation has been sent to {$email}.")
                    ->success()
                    ->send();

            } catch (\Exception $e) {
                // Notify Filament user if email sending failed
                Notification::make()
                    ->title('Email Sending Failed')
                    ->body("Could not send email: {$e->getMessage()}")
                    ->danger()
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('handle', ['record' => $this->record->getKey()]);
    }
}
