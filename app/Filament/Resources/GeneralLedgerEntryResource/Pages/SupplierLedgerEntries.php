<?php

namespace App\Filament\Resources\GeneralLedgerEntryResource\Pages;

use App\Filament\Resources\GeneralLedgerEntryResource;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use App\Models\SupplierLedgerEntry;

class SupplierLedgerEntries extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = GeneralLedgerEntryResource::class;

    protected static string $view = 'filament.resources.general-ledger-entry-resource.pages.supplier-ledger-entries';

    // Configure table
    protected function getTableQuery()
    {
        return SupplierLedgerEntry::query();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('entry_code')->label('Entry Code')->sortable()->searchable(),
            TextColumn::make('supplier.name')->label('Supplier')->sortable()->searchable(),
            TextColumn::make('chartOfAccount.name')->label('Account')->sortable(),
            TextColumn::make('entry_date')->label('Date')->date(),
            TextColumn::make('transaction_name')->label('Transaction')->sortable()->searchable(),
            TextColumn::make('debit')->label('Debit')->money('LKR', true),
            TextColumn::make('credit')->label('Credit')->money('LKR', true),
            TextColumn::make('description')->label('Description')->limit(50),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            // optional filters
        ];
    }
}
