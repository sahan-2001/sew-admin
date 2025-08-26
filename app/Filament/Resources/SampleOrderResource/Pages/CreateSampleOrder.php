<?php

namespace App\Filament\Resources\SampleOrderResource\Pages;

use App\Filament\Resources\SampleOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\SampleOrderCreatedMail;
use Filament\Notifications\Notification;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

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
            try {
                // Generate QR code URL
                $qrCodeData = url('/sample-orders/' . $this->record->order_id . '/' . $this->record->random_code);

                // Generate and store SVG QR code
                $qrCode = new QrCode($qrCodeData);
                $writer = new SvgWriter();
                $result = $writer->write($qrCode);
                $qrCodeFilename = 'qrcode_' . $this->record->order_id . '.svg';
                $path = 'public/qrcodes/' . $qrCodeFilename;
                \Storage::makeDirectory('public/qrcodes');
                \Storage::put($path, $result->getString());

                // Send email
                Mail::to($this->record->customer->email)
                    ->send(new SampleOrderCreatedMail($this->record));

                // Notify Filament user
                Notification::make()
                    ->title('Email Sent Successfully')
                    ->body('Sample order confirmation has been sent to ' . $this->record->customer->email)
                    ->success()
                    ->send();

            } catch (\Exception $e) {
                // Notify if email failed
                Notification::make()
                    ->title('Email Sending Failed')
                    ->body('Could not send email: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('handle', [
            'record' => $this->record->getKey(),
        ]);
    }
}
