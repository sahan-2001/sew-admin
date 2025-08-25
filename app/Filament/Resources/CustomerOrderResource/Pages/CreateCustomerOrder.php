<?php

namespace App\Filament\Resources\CustomerOrderResource\Pages;

use App\Filament\Resources\CustomerOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\CustomerOrderCreatedMail; 
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Storage;

class CreateCustomerOrder extends CreateRecord
{
    protected static string $resource = CustomerOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['random_code'] = strtoupper(Str::random(16));
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->loadMissing(['orderItems', 'customer']);
        $this->record->refresh();

        if ($this->record->customer && $this->record->customer->email) {
            // Generate QR code URL
            $qrCodeUrl = url('/customer-orders/' . $this->record->order_id . '/' . $this->record->random_code);

            // Generate and save QR code SVG
            $qrCode = new QrCode($qrCodeUrl);
            $writer = new SvgWriter();
            $result = $writer->write($qrCode);

            $qrCodeFilename = 'qrcode_customer_' . $this->record->order_id . '.svg';
            $path = 'public/qrcodes/' . $qrCodeFilename;

            Storage::makeDirectory('public/qrcodes');
            Storage::put($path, $result->getString());

            // Send email to customer
            Mail::to($this->record->customer->email)
                ->send(new CustomerOrderCreatedMail($this->record));
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('handle', [
            'record' => $this->record->getKey(),
        ]);
    }
}
