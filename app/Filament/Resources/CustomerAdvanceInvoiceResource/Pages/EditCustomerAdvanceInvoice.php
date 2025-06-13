<?php

namespace App\Filament\Resources\CustomerAdvanceInvoiceResource\Pages;

use App\Filament\Resources\CustomerAdvanceInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerAdvanceInvoice extends EditRecord
{
    protected static string $resource = CustomerAdvanceInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
