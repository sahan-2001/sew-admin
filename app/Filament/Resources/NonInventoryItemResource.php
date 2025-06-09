<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NonInventoryItemResource\Pages;
use App\Models\NonInventoryItem;
use App\Models\NonInventoryCategory;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;

class NonInventoryItemResource extends Resource
{
    protected static ?string $model = NonInventoryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Inventory Management'; 

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('item_id')
                    ->label('Item ID')
                    ->disabled()
                    ->default(fn () => self::generateItemId()),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('non_inventory_category_id')
                    ->label('Category')
                    ->options(fn () => self::getCategoryOptions())
                    ->required(),

                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->required(),

                Forms\Components\Textarea::make('remarks')
                    ->label('Remarks')
                    ->nullable(),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_id')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('price')->sortable(),
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->recordUrl(null);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNonInventoryItems::route('/'),
            'create' => Pages\CreateNonInventoryItem::route('/create'),
            'edit' => Pages\EditNonInventoryItem::route('/{record}/edit'),
        ];
    }

    protected static function generateItemId(): string
    {
        $lastItem = NonInventoryItem::latest('created_at')->first();
        $nextId = $lastItem ? (int) substr($lastItem->item_id, 3) + 1 : 1;
        return 'NI' . str_pad($nextId, 5, '0', STR_PAD_LEFT);
    }

    protected static function getCategoryOptions(): array
    {
        return NonInventoryCategory::pluck('name', 'id')->toArray();
    }

    public static function addCategory(array $data): void
    {
        $categoryName = ucfirst(trim($data['new_category']));

        if (!NonInventoryCategory::where('name', $categoryName)->exists()) {
            NonInventoryCategory::create([
                'name' => $categoryName,
                'created_by' => auth()->id(),
            ]);
        }
    }

}
