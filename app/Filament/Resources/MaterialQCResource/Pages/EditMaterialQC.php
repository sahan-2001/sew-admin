<?php

namespace App\Filament\Resources\MaterialQCResource\Pages;

use App\Filament\Resources\MaterialQCResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterialQC extends EditRecord
{
    protected static string $resource = MaterialQCResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
