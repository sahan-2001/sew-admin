<?php

namespace App\Filament\Resources\ThirdPartyServiceResource\Pages;

use App\Filament\Resources\ThirdPartyServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditThirdPartyService extends EditRecord
{
    protected static string $resource = ThirdPartyServiceResource::class;

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
