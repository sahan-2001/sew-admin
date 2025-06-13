<?php

namespace App\Filament\Resources\SupplierAdvanceInvoiceResource\Pages;

use App\Filament\Resources\SupplierAdvanceInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierAdvanceInvoice extends EditRecord
{
    protected static string $resource = SupplierAdvanceInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
