<?php

namespace App\Filament\Resources\SampleOrderResource\Pages;

use App\Filament\Resources\SampleOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;


class CreateSampleOrder extends CreateRecord
{
    protected static string $resource = SampleOrderResource::class;
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
