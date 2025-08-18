<?php

namespace App\Filament\Resources\FinalProductQCResource\Pages;

use App\Filament\Resources\FinalProductQCResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFinalProductQC extends EditRecord
{
    protected static string $resource = FinalProductQCResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
