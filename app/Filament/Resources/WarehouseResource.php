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
    protected static ?string $navigationGroup = 'Warehouse Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true), // Enforce uniqueness, but allow editing without conflicts
                Forms\Components\TextInput::make('address_line_1')
                    ->required(),
                Forms\Components\TextInput::make('address_line_2'),
                Forms\Components\TextInput::make('address_line_3'),
                Forms\Components\TextInput::make('city')
                    ->required(),
                Forms\Components\TextInput::make('note'),
                Forms\Components\TextInput::make('capacity_length')
                    ->required(),
                Forms\Components\TextInput::make('capacity_width')
                    ->required(),
                Forms\Components\TextInput::make('capacity_height')
                    ->required(),
                Forms\Components\Select::make('measurement_unit')
                    ->options([
                        'm' => 'Meters',
                        'cm' => 'Centimeters',
                        'ft' => 'Feet',
                        'in' => 'Inches',
                    ])
                    ->required(),
                // Set user_id to logged-in user
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('address_line_1')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('city')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('capacity_length')->sortable(),
                Tables\Columns\TextColumn::make('capacity_width')->sortable(),
                Tables\Columns\TextColumn::make('capacity_height')->sortable(),
                Tables\Columns\TextColumn::make('measurement_unit')->sortable(),
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
            ])
            ->recordUrl(null);
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