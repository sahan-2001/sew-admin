<?php

namespace App\Filament\Resources\EndOfDayReportResource\Pages;

use App\Filament\Resources\EndOfDayReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEndOfDayReports extends ListRecords
{
    protected static string $resource = EndOfDayReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
