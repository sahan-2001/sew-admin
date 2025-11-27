<?php

namespace App\Filament\Resources\TransactionSetupResource\Pages;

use App\Filament\Resources\TransactionSetupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransactionSetup extends EditRecord
{
    protected static string $resource = TransactionSetupResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
