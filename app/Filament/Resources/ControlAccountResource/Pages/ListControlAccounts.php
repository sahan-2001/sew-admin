<?php

namespace App\Filament\Resources\ControlAccountResource\Pages;

use App\Filament\Resources\ControlAccountResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ChartOfAccount;

class ListControlAccounts extends ListRecords
{
    protected static string $resource = ControlAccountResource::class;

    protected function getTableQuery(): ?Builder
    {
        // Load all ChartOfAccounts where is_control_account = true
        return ChartOfAccount::query()
            ->where('is_control_account', true);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('customer')
                ->label('Customer Control Account')
                ->color('success')
                ->icon('heroicon-o-user-group')
                ->url(route('filament.admin.resources.control-accounts.customer')),

            Actions\Action::make('supplier')
                ->label('Supplier Control Account')
                ->color('warning')
                ->icon('heroicon-o-truck')
                ->url(route('filament.admin.resources.control-accounts.supplier')),

            Actions\Action::make('vat')
                ->label('VAT Control Account')
                ->color('info')
                ->icon('heroicon-o-banknotes')
                ->url(route('filament.admin.resources.control-accounts.vat')),
        ];
    }
}
