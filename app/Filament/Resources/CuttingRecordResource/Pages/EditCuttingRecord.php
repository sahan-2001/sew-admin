<?php

namespace App\Filament\Resources\CuttingRecordResource\Pages;

use App\Filament\Resources\CuttingRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCuttingRecord extends EditRecord
{
    protected static string $resource = CuttingRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
