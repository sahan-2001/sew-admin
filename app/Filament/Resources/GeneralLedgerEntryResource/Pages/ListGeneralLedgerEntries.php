<?php

namespace App\Filament\Resources\GeneralLedgerEntryResource\Pages;

use App\Filament\Resources\GeneralLedgerEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGeneralLedgerEntries extends ListRecords
{
    protected static string $resource = GeneralLedgerEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
