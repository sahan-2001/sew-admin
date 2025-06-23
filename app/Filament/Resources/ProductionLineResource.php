<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionLineResource\Pages;
use App\Filament\Resources\ProductionLineResource\RelationManagers;
use App\Models\ProductionLine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;

class ProductionLineResource extends Resource
{
    protected static ?string $model = ProductionLine::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Production Lines';
    protected static ?string $navigationGroup = 'Production Management';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            
            Forms\Components\Textarea::make('description')
                ->nullable(),
            
        ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('status')->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
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
        ->filters([
            Tables\Filters\Filter::make('Active')->query(fn (Builder $query) => $query->where('status', 'active')),
            Tables\Filters\Filter::make('Inactive')->query(fn (Builder $query) => $query->where('status', 'inactive')),
        ])
        ->actions([
                Tables\Actions\Action::make('toggleStatus')
                    ->label(fn ($record) => $record->status === 'active' ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->status === 'active' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->status === 'active' ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => auth()->user()->can('edit production lines'))
                    ->action(function ($record) {
                        $record->status = $record->status === 'active' ? 'inactive' : 'active';
                        $record->save();

                        \Filament\Notifications\Notification::make()
                            ->title('Status Updated')
                            ->body("Production Line has been set to '{$record->status}'.")
                            ->success()
                            ->send();
                    }),
        
                EditAction::make()
                    ->visible(fn ($record) => 
                        auth()->user()->can('edit production lines') 
                    ),

                DeleteAction::make()
                    ->visible(fn ($record) => 
                        auth()->user()->can('delete production lines') 
                    ),
            ])
            ->recordUrl(null);
}


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductionLines::route('/'),
            'create' => Pages\CreateProductionLine::route('/create'),
            'edit' => Pages\EditProductionLine::route('/{record}/edit'),
        ];
    }
}
