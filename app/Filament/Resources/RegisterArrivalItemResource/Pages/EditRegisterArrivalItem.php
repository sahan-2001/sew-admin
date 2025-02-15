<?php

namespace App\Filament\Resources\RegisterArrivalItemResource\Pages;

use App\Filament\Resources\RegisterArrivalItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRegisterArrivalItem extends EditRecord
{
    protected static string $resource = RegisterArrivalItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
