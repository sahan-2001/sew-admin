<?php

namespace App\Filament\Resources\PurchaseQuotationResource\Pages;

use App\Filament\Resources\PurchaseQuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseQuotations extends ListRecords
{
    protected static string $resource = PurchaseQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create purchase quotations')),
        ];
    }
}
