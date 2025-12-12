<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryLocationResource\Pages;
use App\Models\InventoryLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\{TextInput, DatePicker, Select, Textarea, FileUpload, Grid, Section, Repeater};


class InventoryLocationResource extends Resource
{
    protected static ?string $model = InventoryLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Inventory Item Management'; 
    protected static ?int $navigationSort = 24;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('view inventory locations') ?? false;
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Warehouse Details')
                ->schema([
                    Forms\Components\Select::make('warehouse_id')
                        ->label('Warehouse')
                        ->searchable()
                        ->options(function () {
                            return \App\Models\Warehouse::all()
                                ->mapWithKeys(fn($w) => ["{$w->id}" => "ID - {$w->id} | Name - {$w->name}"])
                                ->toArray();
                        })
                        ->required()
                ]),

                Section::make('Location Details')
                ->columns(2)
                ->schema([
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
                    ]),

                Section::make('Remarks / Notes')
                ->schema([
                    Forms\Components\TextInput::make('note')
                        ->label('Note')
                        ->required()
                        ->default('No notes provided'),
                ]),

                Section::make('Capacity')
                ->columns(2)
                ->schema([
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
                ]),

                Forms\Components\Hidden::make('created_by')
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
                    ->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Warehouse')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name') 
                    ->sortable(),
                Tables\Columns\TextColumn::make('location_type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('capacity')
                    ->sortable(),
                Tables\Columns\TextColumn::make('measurement_unit')
                    ->sortable(),
                ...(
                Auth::user()->can('view audit columns')
                    ? [
                        TextColumn::make('created_by')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                        TextColumn::make('updated_at')->label('Updated At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                    ]
                    : []
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location_type')
                    ->label('Location Type')
                    ->options([
                        'arrival' => 'Arrival',
                        'picking' => 'Picking',
                        'shipment' => 'Shipment',
                    ])
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (InventoryLocation $record) => auth()->user()->can('edit inventory locations')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (InventoryLocation $record) =>
                        auth()->user()->can('delete inventory locations')
                        && $record->status !== 'active'
                    ),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete inventory locations')),
            ])
        ->defaultSort('id', 'desc') 
        ->recordUrl(null);
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