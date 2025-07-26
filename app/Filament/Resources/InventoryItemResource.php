<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryItemResource\Pages;
use App\Models\InventoryItem;
use App\Models\Category;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions;
use Filament\Forms\Components\{TextInput, DatePicker, Select, Textarea, FileUpload, Grid, Section, Repeater};



class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Inventory Management'; 

     static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Section::make('Item Code')
                ->schema([
                    Forms\Components\TextInput::make('item_code')
                        ->label('Item Code')
                        ->disabled()
                        ->default(fn () => self::generateItemCode()),
                ]),

                Section::make('Item Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Item Name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('category')
                        ->label('Category')
                        ->options(fn () => self::getCategoryOptions())
                        ->required(),
                    Forms\Components\Select::make('uom')
                        ->label('Unit of Measure')
                        ->options([
                            'kg' => 'Kg',
                            'liters' => 'Liters',
                            'meters' => 'Meters',
                            'pcs' => 'Pcs',
                            // ...other units...
                        ])
                        ->required(),
                ]),
                
                Section::make('Additional Information')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('moq')
                        ->label('Alert Quantity / MOQ')
                        ->numeric()
                        ->nullable(),
                    Forms\Components\TextInput::make('max_order_quantity')
                        ->label('Maximum Order Quantity')
                        ->numeric()
                        ->nullable(),
                    Forms\Components\Textarea::make('special_note')
                        ->label('Notes')
                        ->nullable()
                        ->columns(3),
                ]),

                Forms\Components\TextInput::make('available_quantity')
                    ->label('Available Quantity')
                    ->hidden()
                    ->default(0)
                    ->numeric(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('item_code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('category')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('uom')->label('UOM'),
                Tables\Columns\TextColumn::make('available_quantity')->sortable(),
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
                Tables\Filters\SelectFilter::make('uom')
                    ->label('Unit of Measure')
                    ->options(
                        \App\Models\InventoryItem::query()
                            ->distinct()
                            ->pluck('uom', 'uom') 
                            ->filter() 
                            ->toArray()
                    ),

                Tables\Filters\Filter::make('available_quantity_range')
                    ->label('Available Quantity')
                    ->form([
                        Forms\Components\TextInput::make('min')
                            ->label('Min')
                            ->numeric()
                            ->placeholder('e.g. 0'),
                        Forms\Components\TextInput::make('max')
                            ->label('Max')
                            ->numeric()
                            ->placeholder('e.g. 100'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['min'], fn ($q) => $q->where('available_quantity', '>=', $data['min']))
                            ->when($data['max'], fn ($q) => $q->where('available_quantity', '<=', $data['max']));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (InventoryItem $record) => auth()->user()->can('edit inventory items')),
                Tables\Actions\DeleteAction::make()
                ->visible(fn (InventoryItem $record) =>
                    auth()->user()->can('delete inventory items') &&
                    $record->available_quantity < 1
                ),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete inventory items'))
                    ->before(function (Collection $records) {
                        $blocked = $records->filter(fn ($record) => $record->available_quantity >= 1);

                        if ($blocked->isNotEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot delete selected items')
                                ->body('One or more items have available quantity and cannot be deleted.')
                                ->danger()
                                ->send();

                            abort(403, 'Deletion blocked: Items with available quantity ≥ 1');
                        }
                    }),
            ])
        ->defaultSort('id', 'desc') 
        ->recordUrl(null);
    }

    public static function getRelations(): array
    {
        return [
            // Define any related models or relations
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryItems::route('/'),
            'create' => Pages\CreateInventoryItem::route('/create'),
            'edit' => Pages\EditInventoryItem::route('/{record}/edit'),
        ];
    }

    protected static function generateItemCode(): string
    {
        $lastItem = InventoryItem::latest()->first();
        $nextId = $lastItem ? $lastItem->id + 1 : 1;
        $categoryCode = strtoupper(substr(request()->input('category', 'CAT'), 0, 3));
        return $categoryCode . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    protected static function getCategoryOptions(): array
    {
        return Category::pluck('name', 'name')->toArray();
    }

    public static function addCategory(array $data)
    {
        $categoryName = ucfirst($data['new_category']);
        if (!Category::where('name', $categoryName)->exists()) {
            Category::create(['name' => $categoryName]);
        }
    }
}