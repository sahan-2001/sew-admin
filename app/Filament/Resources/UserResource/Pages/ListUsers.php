<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getActions(): array
    {
        $actions = [];

        if (auth()->user()->hasAnyRole(['admin', 'superuser', 'manager'])) {
            $actions[] = Actions\CreateAction::make();
        }

        return $actions;
    }
}