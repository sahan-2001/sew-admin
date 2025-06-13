<?php

namespace App\Filament\Resources\SupplierAdvanceInvoiceResource\Pages;

use App\Filament\Resources\SupplierAdvanceInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupplierAdvanceInvoices extends ListRecords
{
    protected static string $resource = SupplierAdvanceInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
