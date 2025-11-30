<?php

namespace App\Filament\Resources\ControlAccountResource\Pages;

use App\Filament\Resources\ControlAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditControlAccount extends EditRecord
{
    protected static string $resource = ControlAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
