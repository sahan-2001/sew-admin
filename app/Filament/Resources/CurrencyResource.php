<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyResource\Pages;
use App\Models\Currency;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\{
    TextInput,
    Toggle,
    Section
};
use Filament\Tables\Columns\{
    TextColumn,
    IconColumn
};
use Filament\Tables;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Currencies';
    protected static ?string $modelLabel = 'Currency';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Currency Information')
                    ->schema([
                        TextInput::make('code')
                            ->label('Currency Code')
                            ->required()
                            ->maxLength(3)
                            ->unique(ignoreRecord: true)
                            ->placeholder('USD, LKR, EUR'),

                        TextInput::make('name')
                            ->required()
                            ->placeholder('US Dollar, Sri Lankan Rupee'),

                        TextInput::make('symbol')
                            ->maxLength(5)
                            ->placeholder('$, Rs, €'),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('symbol')
                    ->label('Symbol'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'edit' => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}
