<?php

namespace App\Filament\Resources\VatGroupResource\Pages;

use App\Filament\Resources\VatGroupResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListVatGroups extends ListRecords
{
    protected static string $resource = VatGroupResource::class;

    // Make title public
    public function getTitle(): string
    {
        return 'VAT Groups';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('customer_vat_groups')
                ->label('Customer VAT Groups')
                ->url($this->getResource()::getUrl('customer')),

            Action::make('inventory_item_vat_groups')
                ->label('Inventory Item VAT Groups')
                ->url($this->getResource()::getUrl('inventory_item')),

            Action::make('non_inventory_item_vat_groups')
                ->label('Non-Inventory Item VAT Groups')
                ->url($this->getResource()::getUrl('non_inventory_item')),

            Action::make('supplier_vat_groups')
                ->label('Supplier VAT Groups')
                ->url($this->getResource()::getUrl('supplier')),
        ];
    }
}
