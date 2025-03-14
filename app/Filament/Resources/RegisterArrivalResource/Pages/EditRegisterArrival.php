<?php

namespace App\Filament\Resources\RegisterArrivalResource\Pages;

use App\Filament\Resources\RegisterArrivalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRegisterArrival extends EditRecord
{
    protected static string $resource = RegisterArrivalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
