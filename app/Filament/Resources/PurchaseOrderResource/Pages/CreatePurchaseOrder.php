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

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['random_code'] = strtoupper(Str::random(16));
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->loadMissing(['items', 'invoice', 'supplierAdvanceInvoices']);
        $this->record->refresh();

        $email = null;

        if ($this->record->provider_type === 'customer') {
            $customer = \App\Models\Customer::find($this->record->provider_id);
            if ($customer && $customer->email) {
                $email = $customer->email;
            }
        }

        if ($this->record->provider_type === 'supplier') {
            $supplier = \App\Models\Supplier::find($this->record->provider_id);
            if ($supplier && $supplier->email) {
                $email = $supplier->email;
            }
        }

        if ($email) {
            // Generate QR code URL
            $qrCodeData = url('/purchase-orders/' . $this->record->id . '/' . $this->record->random_code);

            // Generate & store SVG QR code
            $qrCode = new \Endroid\QrCode\QrCode($qrCodeData);
            $writer = new \Endroid\QrCode\Writer\SvgWriter();
            $result = $writer->write($qrCode);

            $qrCodeFilename = 'purchase_qrcode_' . $this->record->id . '.svg';
            $path = 'public/qrcodes/' . $qrCodeFilename;

            \Storage::makeDirectory('public/qrcodes');
            \Storage::put($path, $result->getString());

            // Send email
            \Mail::to($email)->send(
                new \App\Mail\PurchaseOrderCreatedMail($this->record)
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('handle', ['record' => $this->record->getKey()]);
    }
}
