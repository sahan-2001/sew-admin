<?php

namespace App\Filament\Resources\GeneralLedgerEntryResource\Pages;

use App\Filament\Resources\GeneralLedgerEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGeneralLedgerEntry extends EditRecord
{
    protected static string $resource = GeneralLedgerEntryResource::class;

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
