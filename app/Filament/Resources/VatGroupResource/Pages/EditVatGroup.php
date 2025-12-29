<?php

namespace App\Filament\Resources\VatGroupResource\Pages;

use App\Filament\Resources\VatGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVatGroup extends EditRecord
{
    protected static string $resource = VatGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
