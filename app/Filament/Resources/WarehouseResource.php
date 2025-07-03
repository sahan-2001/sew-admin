<?php
namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Warehouses';
    protected static ?string $navigationGroup = 'Inventory Management'; 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true), 
                        Forms\Components\TextInput::make('note')->label('Notes'),
                    ]),
                
                Forms\Components\Section::make('Location Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('address_line_1')
                            ->required(),
                        Forms\Components\TextInput::make('address_line_2'),
                        Forms\Components\TextInput::make('address_line_3'),
                        Forms\Components\TextInput::make('city')
                            ->required(),
                    ]),
                
                Forms\Components\Section::make('Capacity of Warehouse')
                    ->columns(2)
                    ->schema([
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
                    ]),
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Warehouse ID')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                Tables\Columns\TextColumn::make('name') 
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('capacity_length')->sortable(),
                Tables\Columns\TextColumn::make('capacity_width')->sortable(),
                Tables\Columns\TextColumn::make('capacity_height')->sortable(),
                Tables\Columns\TextColumn::make('measurement_unit'),
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
                Filter::make('id')
                    ->label('Warehouse ID')
                    ->form([
                        Forms\Components\TextInput::make('id')
                            ->placeholder('Enter Warehouse ID'),
                    ])
                    ->query(fn ($query, array $data) =>
                        $query->when($data['id'],
                            fn ($query, $id) => $query->where('id', 'like', "%{$id}%")
                        )
                    ),

                Filter::make('name')
                    ->label('Name')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->placeholder('Enter Warehouse Name'),
                    ])
                    ->query(fn ($query, array $data) =>
                        $query->when($data['name'],
                            fn ($query, $name) => $query->where('name', 'like', "%{$name}%")
                        )
                    ),

                Filter::make('measurement_unit')
                    ->label('Measurement Unit')
                    ->form([
                        Forms\Components\Select::make('measurement_unit')
                            ->options([
                                'cm' => 'cm',
                                'm' => 'm',
                                'inch' => 'inch',
                                // Add others as needed
                            ])
                            ->placeholder('Select Unit'),
                    ])
                    ->query(fn ($query, array $data) =>
                        $query->when($data['measurement_unit'],
                            fn ($query, $unit) => $query->where('measurement_unit', $unit)
                        )
                    ),
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
        ->defaultSort('id', 'desc') 
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