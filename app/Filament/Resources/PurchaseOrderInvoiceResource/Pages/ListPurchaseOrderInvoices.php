<?php

namespace App\Filament\Resources\PurchaseOrderInvoiceResource\Pages;

use App\Filament\Resources\PurchaseOrderInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrderInvoices extends ListRecords
{
    protected static string $resource = PurchaseOrderInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
