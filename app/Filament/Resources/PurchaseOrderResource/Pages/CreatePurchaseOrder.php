<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['random_code'] = strtoupper(Str::random(16));
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}