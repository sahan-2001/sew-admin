<?php

namespace App\Filament\Resources\PurchaseOrderInvoiceResource\Pages;

use App\Filament\Resources\PurchaseOrderInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrderInvoice extends EditRecord
{
    protected static string $resource = PurchaseOrderInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
