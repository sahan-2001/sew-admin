<?php

namespace App\Filament\Resources\RecordDailyEmployeePerformanceResource\Pages;

use App\Filament\Resources\RecordDailyEmployeePerformanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecordDailyEmployeePerformance extends EditRecord
{
    protected static string $resource = RecordDailyEmployeePerformanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
