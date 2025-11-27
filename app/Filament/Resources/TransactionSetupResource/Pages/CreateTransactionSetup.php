<?php

namespace App\Filament\Resources\TransactionSetupResource\Pages;

use App\Filament\Resources\TransactionSetupResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransactionSetup extends CreateRecord
{
    protected static string $resource = TransactionSetupResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
