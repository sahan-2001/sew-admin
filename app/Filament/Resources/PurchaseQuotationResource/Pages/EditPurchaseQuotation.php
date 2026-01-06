<?php

namespace App\Filament\Resources\PurchaseQuotationResource\Pages;

use App\Filament\Resources\PurchaseQuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseQuotation extends EditRecord
{
    protected static string $resource = PurchaseQuotationResource::class;

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
