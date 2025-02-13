<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryLocationResource\Pages;
use App\Models\InventoryLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class InventoryLocationResource extends Resource
{
    protected static ?string $model = InventoryLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';
    protected static ?string $navigationLabel = 'Inventory Locations';
    protected static ?string $navigationGroup = 'Inventory Management';

    

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(function () {
                        return \App\Models\Warehouse::all()->pluck('name', 'id');
                    })
                    ->required(),
    
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->unique(
                        table: InventoryLocation::class,
                        column: 'name',
                        ignoreRecord: true
                    ),
    
                Forms\Components\Select::make('location_type')
                    ->options([
                        'arrival' => 'Arrival',
                        'picking' => 'Picking',
                        'shipment' => 'Shipment',
                    ])
                    ->default('picking')
                    ->required(),
    
                Forms\Components\TextInput::make('note')
                    ->label('Note')
                    ->required()
                    ->default('No notes provided'),
    
                Forms\Components\TextInput::make('capacity')
                    ->label('Capacity')
                    ->required()
                    ->default('0'),
    
                Forms\Components\Select::make('measure_of_capacity')
                    ->options([
                        'liters' => 'Liters',
                        'm^3' => 'Cubic Meters',
                        'cm^3' => 'Cubic Centimeters',
                        'box' => 'Box',
                        'pallets' => 'Pallets',
                        'other' => 'Other',
                    ])
                    ->required()
                    ->default('liters'),
    
                Forms\Components\Hidden::make('created_by')
                    ->default(fn () => auth()->id()),
            ]);
    }
    

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Warehouse')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('location_type')
                    ->label('Location Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacity')
                    ->sortable(),
                Tables\Columns\TextColumn::make('measure_of_capacity')
                    ->label('Measure of Capacity')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->sortable()
                    ->dateTime(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (InventoryLocation $record) => auth()->user()->can('edit inventory locations')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (InventoryLocation $record) => auth()->user()->can('delete inventory locations')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete inventory locations')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryLocations::route('/'),
            'create' => Pages\CreateInventoryLocation::route('/create'),
            'edit' => Pages\EditInventoryLocation::route('/{record}/edit'),
        ];
    }
}
