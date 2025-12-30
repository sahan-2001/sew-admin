<?php

namespace App\Filament\Resources\NonInventoryItemResource\Pages;

use App\Filament\Resources\NonInventoryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNonInventoryItem extends EditRecord
{
    protected static string $resource = NonInventoryItemResource::class;

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
