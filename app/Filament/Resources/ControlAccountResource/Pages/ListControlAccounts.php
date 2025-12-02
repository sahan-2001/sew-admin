<?php

namespace App\Filament\Resources\ControlAccountResource\Pages;

use App\Filament\Resources\ControlAccountResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ChartOfAccount;

class ListControlAccounts extends ListRecords
{
    protected static string $resource = ControlAccountResource::class;

    protected function getTableQuery(): ?Builder
    {
        return ChartOfAccount::query()
            ->where('is_control_account', true);
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\ControlAccountResource\Widgets\ControlAccountButtons::class,
        ];
    }

}
