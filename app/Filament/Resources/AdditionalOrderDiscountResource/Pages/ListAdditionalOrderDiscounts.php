<?php

namespace App\Filament\Resources\AdditionalOrderDiscountResource\Pages;

use App\Filament\Resources\AdditionalOrderDiscountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdditionalOrderDiscounts extends ListRecords
{
    protected static string $resource = AdditionalOrderDiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
