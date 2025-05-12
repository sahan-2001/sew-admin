<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockOverviewResource\Pages;
use App\Models\Stock;
use App\Models\InventoryItem;
use App\Models\Warehouse;
use App\Models\InventoryLocation;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class StockOverviewResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'Stocks';
    protected static ?string $navigationGroup = 'Inventory Management'; 

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('location.warehouse.name')
                ->label('Warehouse')
                ->sortable(),

            TextColumn::make('location.name')
                ->label('Location')
                ->sortable(),

            TextColumn::make('item.item_code')
                ->label('Item Code')
                ->sortable()
                ->searchable(),

            TextColumn::make('item.name')
                ->label('Item Name')
                ->sortable()
                ->searchable(),

            TextColumn::make('quantity')
                ->label('Available Quantity')
                ->sortable(),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('warehouse_id')
                ->label('Filter by Warehouse')
                ->relationship('location.warehouse', 'name'),

            Tables\Filters\SelectFilter::make('location_id')
                ->label('Filter by Location')
                ->relationship('location', 'name'),
        ]);
}


    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockOverview::route('/'),
        ];
    }
}
