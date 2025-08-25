<?php

namespace App\Filament\Resources\SampleOrderResource\Pages;

use App\Filament\Resources\SampleOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\SampleOrderCreatedMail;

class CreateSampleOrder extends CreateRecord
{
    protected static string $resource = SampleOrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['random_code'] = strtoupper(Str::random(16));
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->loadMissing(['items', 'customer']); 
        $this->record->refresh(); 

        if ($this->record->customer && $this->record->customer->email) {
            // Generate QR code URL
            $qrCodeData = url('/sample-orders/' . $this->record->order_id . '/' . $this->record->random_code);

            // You can optionally generate and store an SVG if you want the file
            $qrCode = new \Endroid\QrCode\QrCode($qrCodeData);
            $writer = new \Endroid\QrCode\Writer\SvgWriter();
            $result = $writer->write($qrCode);
            $qrCodeFilename = 'qrcode_' . $this->record->order_id . '.svg';
            $path = 'public/qrcodes/' . $qrCodeFilename;
            \Storage::makeDirectory('public/qrcodes');
            \Storage::put($path, $result->getString());

            // Send email with QR code link
            \Mail::to($this->record->customer->email)
                ->send(new \App\Mail\SampleOrderCreatedMail($this->record, $qrCodeData));
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('handle', [
            'record' => $this->record->getKey(),
        ]);
    }
}
