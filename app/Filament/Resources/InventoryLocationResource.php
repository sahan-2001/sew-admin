<?php
// filepath: /C:/Users/User/Desktop/Sahan_Personal Files/Academics/project/sew-admin/app/Filament/Resources/InventoryLocationResource.php
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

class InventoryLocationResource extends Resource
{
    protected static ?string $model = InventoryLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';
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
                    ->required(), // Warehouse must be selected

                // Name field (required, enforce uniqueness)
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->unique(
                        table: InventoryLocation::class,
                        column: 'name',
                        ignoreRecord: true // Allows editing without triggering uniqueness error for the same record
                    ),

                // Location Type (required)
                Forms\Components\Select::make('location_type')
                    ->options([
                        'arrival' => 'Arrival',
                        'picking' => 'Picking',
                        'shipment' => 'Shipment',
                    ])
                    ->default('picking')
                    ->required(), // Location type must be selected

                // Note (required, default value to avoid null)
                Forms\Components\TextInput::make('note')
                    ->label('Note')
                    ->required()
                    ->default('No notes provided'),

                // Capacity (required, default value to avoid null)
                Forms\Components\TextInput::make('capacity')
                    ->label('Capacity')
                    ->required()
                    ->default('0'),

                // Measure of Capacity (required, default value to avoid null)
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

                // User ID will be set automatically to the logged-in user
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
                Tables\Columns\TextColumn::make('name') // Add name column to the table
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
                        TextColumn::make('created_by')->label('Created By')->toggleable()->sortable(),
                        TextColumn::make('updated_by')->label('Updated By')->toggleable()->sortable(),
                        TextColumn::make('created_at')->label('Created At')->toggleable()->dateTime()->sortable(),
                        TextColumn::make('updated_at')->label('Updated At')->toggleable()->dateTime()->sortable(),
                    ]
                    : []
                    ),
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
            ])
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