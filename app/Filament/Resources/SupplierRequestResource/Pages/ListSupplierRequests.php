<?php

namespace App\Filament\Resources\SupplierRequestResource\Pages;

use App\Filament\Resources\SupplierRequestResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions;
use Filament\Tables;

class ListSupplierRequests extends ListRecords
{
    protected static string $resource = SupplierRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create supplier requests')),
        ];
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('shop_name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('address')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('email')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('phone_1')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('phone_2')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('outstanding_balance')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('requestedBy.email')->label('Requested By Email')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('approvedBy.name')->label('Approved By')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('status')->sortable()->searchable(),
        ];
    }
}