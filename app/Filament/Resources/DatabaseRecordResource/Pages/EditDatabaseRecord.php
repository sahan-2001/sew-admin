<?php

namespace App\Filament\Resources\DatabaseRecordResource\Pages;

use App\Filament\Resources\DatabaseRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDatabaseRecord extends EditRecord
{
    protected static string $resource = DatabaseRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
