<?php

namespace App\Filament\Resources\ChartOfAccountResource\Pages;

use App\Filament\Resources\ChartOfAccountResource;
use App\Models\ChartOfAccount;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateChartOfAccount extends CreateRecord
{
    protected static string $resource = ChartOfAccountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-format account code (trim + uppercase)
        $data['code'] = strtoupper(trim($data['code']));
        return $data;
    }

    protected function beforeCreate(): ?bool
    {
        $code = strtoupper(trim($this->data['code'] ?? ''));

        // Check if code already exists
        if (ChartOfAccount::where('code', $code)->exists()) {
            Notification::make()
                ->title('Duplicate Account Code')
                ->body("An account with the code **{$code}** already exists. Please use a unique code.")
                ->danger()
                ->persistent()
                ->send();

            // Stop the creation process
            return false;
        }

        return true;
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Chart of Account Created')
            ->body("Account **{$this->record->name} ({$this->record->code})** has been successfully created.")
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }
}
