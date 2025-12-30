<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VatGroupResource\Pages;
use App\Models\CustomerVatGroup;
use App\Models\InventoryItemVatGroup;
use App\Models\NonInventoryItemVatGroup;
use App\Models\SupplierVatGroup;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class VatGroupResource extends Resource
{
    protected static ?string $model = CustomerVatGroup::class; 

    protected static ?string $navigationGroup = 'Accounting & Finance';
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'VAT Groups';
    protected static ?string $label = 'VAT Group';
    protected static ?string $pluralLabel = 'VAT Groups';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_count')
                    ->label('Customer VAT Groups')
                    ->getStateUsing(fn () => CustomerVatGroup::count()),

                TextColumn::make('inventory_count')
                    ->label('Inventory Item VAT Groups')
                    ->getStateUsing(fn () => InventoryItemVatGroup::count()),

                TextColumn::make('non_inventory_count')
                    ->label('Non-Inventory Item VAT Groups')
                    ->getStateUsing(fn () => NonInventoryItemVatGroup::count()),

                TextColumn::make('supplier_count')
                    ->label('Supplier VAT Groups')
                    ->getStateUsing(fn () => SupplierVatGroup::count()),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVatGroups::route('/'), 
            'create' => Pages\CreateVatGroup::route('/create'),
            'edit' => Pages\EditVatGroup::route('/{record}/edit'),

            'customer'          => Pages\CustomerVatGroups::route('/customer'),
            'inventory_item'    => Pages\InventoryItemVatGroups::route('/inventory-item'),
            'non_inventory_item'=> Pages\NonInventoryItemVatGroups::route('/non-inventory-item'),
            'supplier'          => Pages\SupplierVatGroups::route('/supplier'),
        ];
    }
}
