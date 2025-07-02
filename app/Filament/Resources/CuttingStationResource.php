<?php

namespace App\Filament\Resources;

use App\Models\CuttingStation;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\Filter;

class CuttingStationResource extends Resource
{
    protected static ?string $model = CuttingStation::class;

    protected static ?string $navigationIcon = 'heroicon-o-scissors';
    protected static ?string $navigationLabel = 'Cutting Stations';
    protected static ?string $navigationGroup = 'Cutting Department';
    protected static ?string $pluralLabel = 'Cutting Stations';
    protected static ?string $modelLabel = 'Cutting Station';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->maxLength(1000)
                ->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->searchable()
                    ->label('Station ID')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('description')->limit(50)->wrap(),
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
                Filter::make('name')
                    ->label('Filter by Name')
                    ->form([
                        TextInput::make('value')
                            ->label('Name Contains')
                            ->placeholder('Enter name...')
                            ->autocomplete(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when($data['value'], fn ($q, $value) => $q->where('name', 'like', "%{$value}%"));
                    }),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => CuttingStationResource\Pages\ListCuttingStations::route('/'),
            'create' => CuttingStationResource\Pages\CreateCuttingStation::route('/create'),
            'edit' => CuttingStationResource\Pages\EditCuttingStation::route('/{record}/edit'),
        ];
    }
}
