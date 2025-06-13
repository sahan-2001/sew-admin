<?php

namespace App\Filament\Resources\CustomerAdvanceInvoiceResource\Pages;

use App\Filament\Resources\CustomerAdvanceInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerAdvanceInvoices extends ListRecords
{
    protected static string $resource = CustomerAdvanceInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
