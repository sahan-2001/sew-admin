<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function afterCreate(): void
    {
        $employee = $this->record;

        // 1️⃣ Generate Offer Letter PDF
        $pdf = Pdf::loadView('pdf.employee-offer-letter', [
            'employee' => $employee,
        ]);

        $fileName = "offer_letters/{$employee->employee_code}.pdf";
        Storage::disk('public')->put($fileName, $pdf->output());

        // 2️⃣ Show Notification with Employee Code and Download Link
        Notification::make()
            ->title('Employee Created Successfully')
            ->body("Employee Code: {$employee->employee_code}")
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('handle', ['record' => $this->record]);
    }
}
