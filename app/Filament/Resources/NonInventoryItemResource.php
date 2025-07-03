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
use Filament\Forms\Components\{TextInput, DatePicker, Select, Textarea, FileUpload, Grid, Section, Repeater};
use Filament\Tables\Filters\Filter;

class NonInventoryItemResource extends Resource
{
    protected static ?string $model = NonInventoryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Inventory Management'; 

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Section::make('Item Details')
                ->columns(2)
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
                ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Item ID')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category')->searchable(),
                Tables\Columns\TextColumn::make('price')->sortable(),
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
                Filter::make('price')
                    ->form([
                        TextInput::make('price_min')
                            ->label('Min Price')
                            ->numeric()
                            ->placeholder('Min'),
                        TextInput::make('price_max')
                            ->label('Max Price')
                            ->numeric()
                            ->placeholder('Max'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['price_min'], fn ($q) => $q->where('price', '>=', $data['price_min']))
                            ->when($data['price_max'], fn ($q) => $q->where('price', '<=', $data['price_max']));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
        ->defaultSort('id', 'desc') 
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
