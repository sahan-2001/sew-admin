<?php
namespace App\Filament\Resources\SupplierAdvanceInvoiceResource\Pages;

use App\Filament\Resources\SupplierAdvanceInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSupplierAdvanceInvoice extends CreateRecord
{
    protected static string $resource = SupplierAdvanceInvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['grand_total'] = collect($data['purchase_order_items'] ?? [])->sum('total_sale');
        return $data;
    }
}