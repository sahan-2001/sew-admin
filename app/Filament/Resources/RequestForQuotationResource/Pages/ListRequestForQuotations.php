<?php

namespace App\Filament\Resources\RequestForQuotationResource\Pages;

use App\Filament\Resources\RequestForQuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequestForQuotations extends ListRecords
{
    protected static string $resource = RequestForQuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('Create Request For Quotations')),
        ];
    }
}
