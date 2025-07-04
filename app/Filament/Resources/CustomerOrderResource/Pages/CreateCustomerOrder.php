<?php

namespace App\Filament\Resources\CustomerOrderResource\Pages;

use App\Filament\Resources\CustomerOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;


class CreateCustomerOrder extends CreateRecord
{
    protected static string $resource = CustomerOrderResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['random_code'] = strtoupper(Str::random(16));
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('handle', ['record' => $this->record->getKey()]);
    }
}
