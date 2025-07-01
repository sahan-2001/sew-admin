<?php

namespace App\Filament\Resources\ThirdPartyServiceResource\Pages;

use App\Filament\Resources\ThirdPartyServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateThirdPartyService extends CreateRecord
{
    protected static string $resource = ThirdPartyServiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
