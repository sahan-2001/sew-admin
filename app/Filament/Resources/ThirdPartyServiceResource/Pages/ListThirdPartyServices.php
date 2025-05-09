<?php

namespace App\Filament\Resources\ThirdPartyServiceResource\Pages;

use App\Filament\Resources\ThirdPartyServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListThirdPartyServices extends ListRecords
{
    protected static string $resource = ThirdPartyServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
