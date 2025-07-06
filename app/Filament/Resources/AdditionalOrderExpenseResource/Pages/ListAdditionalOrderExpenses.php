<?php

namespace App\Filament\Resources\AdditionalOrderExpenseResource\Pages;

use App\Filament\Resources\AdditionalOrderExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdditionalOrderExpenses extends ListRecords
{
    protected static string $resource = AdditionalOrderExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
