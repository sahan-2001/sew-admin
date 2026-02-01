<?php

namespace App\Filament\Resources\GeneralLedgerEntryResource\Pages;

use App\Filament\Resources\GeneralLedgerEntryResource;
use Filament\Actions\Action; 
use Filament\Resources\Pages\ListRecords;

class ListGeneralLedgerEntries extends ListRecords
{
    protected static string $resource = GeneralLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('supplier-ledger-entries')
                ->label('Supplier Ledger Entries')
                ->icon('heroicon-o-document-text')
                ->url(GeneralLedgerEntryResource::getUrl('supplier-ledger-entries')),
        ];
    }
}
