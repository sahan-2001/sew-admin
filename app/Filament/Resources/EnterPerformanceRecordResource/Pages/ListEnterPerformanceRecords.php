<?php

namespace App\Filament\Resources\EnterPerformanceRecordResource\Pages;

use App\Filament\Resources\EnterPerformanceRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEnterPerformanceRecords extends ListRecords
{
    protected static string $resource = EnterPerformanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
