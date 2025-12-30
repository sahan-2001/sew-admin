<?php

namespace App\Filament\Resources\EndOfDayReportResource\Pages;

use App\Filament\Resources\EndOfDayReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEndOfDayReport extends EditRecord
{
    protected static string $resource = EndOfDayReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
