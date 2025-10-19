<?php

namespace App\Filament\Resources\ControlAccountResource\Pages;

use App\Filament\Resources\ControlAccountResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use App\Models\CustomerControlAccount;
use App\Models\SupplierControlAccount;
use App\Models\VatControlAccount;
use App\Models\ControlAccountSummary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

class ListControlAccounts extends ListRecords
{
    protected static string $resource = ControlAccountResource::class;

    protected function getTableQuery(): ?Builder
    {
        // Return a valid query builder (never used)
        return CustomerControlAccount::query()->whereRaw('1 = 0');
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
