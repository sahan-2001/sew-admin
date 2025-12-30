<?php

namespace App\Filament\Resources\SupplierRequestResource\Pages;

use App\Filament\Resources\SupplierRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupplierRequest extends EditRecord
{
    protected static string $resource = SupplierRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
