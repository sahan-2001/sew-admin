<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryItemResource\Pages;
use App\Models\InventoryItem;
use App\Models\Category;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Inventory Management';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('item_code')
                    ->label('Item Code')
                    ->disabled()
                    ->default(fn () => self::generateItemCode()),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('category')
                    ->label('Category')
                    ->options(fn () => self::getCategoryOptions())
                    ->required(),
                Forms\Components\Textarea::make('special_note')
                    ->label('Special Note')
                    ->nullable(),
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
                Forms\Components\TextInput::make('available_quantity')
                    ->label('Available Quantity')
                    ->default(0)
                    ->numeric(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('category')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('uom')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('available_quantity')->sortable()->searchable(),
            ])
            ->filters([
                // Define your filters if needed
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (InventoryItem $record) => auth()->user()->can('edit inventory items')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (InventoryItem $record) => auth()->user()->can('delete inventory items')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->can('delete inventory items')),
            ]);
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