<?php

namespace App\Filament\Resources\TransactionSetupResource\Pages;

use App\Filament\Resources\TransactionSetupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransactionSetups extends ListRecords
{
    protected static string $resource = TransactionSetupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
