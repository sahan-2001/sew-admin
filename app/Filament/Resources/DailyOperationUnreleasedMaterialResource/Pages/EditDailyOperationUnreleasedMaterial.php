<?php

namespace App\Filament\Resources\DailyOperationUnreleasedMaterialResource\Pages;

use App\Filament\Resources\DailyOperationUnreleasedMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyOperationUnreleasedMaterial extends EditRecord
{
    protected static string $resource = DailyOperationUnreleasedMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
