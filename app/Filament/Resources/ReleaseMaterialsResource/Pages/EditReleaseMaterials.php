<?php

namespace App\Filament\Resources\ReleaseMaterialsResource\Pages;

use App\Filament\Resources\ReleaseMaterialsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReleaseMaterials extends EditRecord
{
    protected static string $resource = ReleaseMaterialsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
