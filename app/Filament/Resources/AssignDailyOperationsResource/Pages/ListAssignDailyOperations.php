<?php

namespace App\Filament\Resources\AssignDailyOperationsResource\Pages;

use App\Filament\Resources\AssignDailyOperationsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAssignDailyOperations extends ListRecords
{
    protected static string $resource = AssignDailyOperationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
