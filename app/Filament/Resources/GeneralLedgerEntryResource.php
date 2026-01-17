<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GeneralLedgerEntryResource\Pages;
use App\Filament\Resources\GeneralLedgerEntryResource\RelationManagers;
use App\Models\GeneralLedgerEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions;
use Filament\Forms\Components\{TextInput, DatePicker, Select, Textarea, FileUpload, Grid, Section, Repeater};


class GeneralLedgerEntryResource extends Resource
{
    protected static ?string $model = GeneralLedgerEntry::class;

    protected static ?string $navigationGroup = 'Accounting & Finance';
    protected static ?string $navigationLabel = 'General Ledger Entries';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entry_code')
                    ->label('Entry Code')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Entry Date')
                    ->date()
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('account.name')
                    ->label('Account')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('control_account_table')
                    ->label('Control Account Table')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('debit')
                    ->label('Debit')
                    ->sortable()
                    ->formatStateUsing(fn($state, $record) => $record->formatted_debit),

                Tables\Columns\TextColumn::make('credit')
                    ->label('Credit')
                    ->sortable()
                    ->formatStateUsing(fn($state, $record) => $record->formatted_credit),

                Tables\Columns\TextColumn::make('transaction_name')
                    ->label('Transaction')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),

                ...(
                Auth::user()->can('view audit columns')
                    ? [
                        TextColumn::make('created_by')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('updated_at')->label('Updated At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                    ]
                    : []
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('account_id')
                    ->label('Account')
                    ->form([
                        Forms\Components\Select::make('account_id')
                            ->label('Select Account')
                            ->options(\App\Models\ChartOfAccount::pluck('name', 'id'))
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query->when($data['account_id'] ?? null, fn($q, $id) => $q->where('account_id', $id));
                    }),
                Tables\Filters\Filter::make('entry_date')
                    ->form([
                        Forms\Components\DatePicker::make('entry_date')
                            ->label('Entry Date'),
                    ])
                    ->query(fn(Builder $query, array $data) => 
                        $query->when($data['entry_date'] ?? null, fn($q, $date) => $q->whereDate('entry_date', $date))
                    ),
            ])
            ->actions([
                
            ])

            ->bulkActions([
            ])

            ->defaultSort('entry_date', 'desc');
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
            'index' => Pages\ListGeneralLedgerEntries::route('/'),
            'create' => Pages\CreateGeneralLedgerEntry::route('/create'),
            'edit' => Pages\EditGeneralLedgerEntry::route('/{record}/edit'),
        ];
    }
}
