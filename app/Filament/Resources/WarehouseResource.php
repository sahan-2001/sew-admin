<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Warehouses';
    protected static ?string $navigationGroup = 'Inventory Management';

    /**
     * Define the form schema for creating and editing warehouses.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Warehouse Name')
                    ->required()
                    ->unique(ignoreRecord: true), // Enforce uniqueness but allow editing without conflicts
                Forms\Components\TextInput::make('address_line_1')
                    ->label('Address Line 1')
                    ->required(),
                Forms\Components\TextInput::make('address_line_2')
                    ->label('Address Line 2'),
                Forms\Components\TextInput::make('address_line_3')
                    ->label('Address Line 3'),
                Forms\Components\TextInput::make('city')
                    ->required()
                    ->label('City'),
                Forms\Components\Textarea::make('note')
                    ->label('Note'),
                Forms\Components\TextInput::make('capacity_length')
                    ->label('Capacity Length')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('capacity_width')
                    ->label('Capacity Width')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('capacity_height')
                    ->label('Capacity Height')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('measurement_unit')
                    ->label('Measurement Unit')
                    ->options([
                        'm' => 'Meters',
                        'cm' => 'Centimeters',
                        'ft' => 'Feet',
                        'in' => 'Inches',
                    ])
                    ->required(),
                Forms\Components\Hidden::make('user_id')
                    ->label('Created By')
                    ->default(fn () => auth()->id()), // Automatically set the logged-in user's ID
            ]);
    }

    /**
     * Define the table columns and actions for listing warehouses.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Warehouse Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('address_line_1')
                    ->label('Address Line 1')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('City')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('capacity_length')
                    ->label('Length')
                    ->sortable(),
                Tables\Columns\TextColumn::make('capacity_width')
                    ->label('Width')
                    ->sortable(),
                Tables\Columns\TextColumn::make('capacity_height')
                    ->label('Height')
                    ->sortable(),
                Tables\Columns\TextColumn::make('measurement_unit')
                    ->label('Unit')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Created By Email')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                // Add any necessary filters here
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (Warehouse $record) => auth()->user()->can('edit warehouses')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Warehouse $record) => auth()->user()->can('delete warehouses')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete warehouses')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),

            
        ];
    }
    
}

