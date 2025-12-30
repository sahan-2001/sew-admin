<?php

namespace App\Filament\Resources\ProductionLineOperationResource\Pages;

use App\Filament\Resources\ProductionLineOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductionLineOperation extends EditRecord
{
    protected static string $resource = ProductionLineOperationResource::class;

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
