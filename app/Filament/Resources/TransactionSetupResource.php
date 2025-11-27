<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionSetupResource\Pages;
use App\Models\TransactionSetup;
use App\Models\TransactionSetupAccount;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;

class TransactionSetupResource extends Resource
{
    protected static ?string $model = TransactionSetup::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Accounting & Finance';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Transaction Setup Details')
                    ->schema([
                        TextInput::make('transaction_name')
                            ->label('Transaction Name')
                            ->disabled()
                            ->required(),
                        TextInput::make('description')
                            ->label('Description')
                            ->disabled(),
                        TextInput::make('status')
                            ->label('Status')
                            ->disabled(),
                    ]),

                Section::make('Transaction Setup Accounts')
                    ->description('Add or modify debit/credit account mappings')
                    ->schema([
                        Repeater::make('transactionSetupAccounts')
                            ->relationship('transactionSetupAccounts')
                            ->schema([
                                Select::make('debit_account_id_1')->label('Debit Account 1')->searchable()
                                    ->options(fn() => \App\Models\ChartOfAccount::all()
                                        ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray())
                                    ->required(),

                                Select::make('credit_account_id_1')->label('Credit Account 1')->searchable()
                                    ->options(fn() => \App\Models\ChartOfAccount::all()
                                        ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray())
                                    ->required(),

                                Select::make('debit_account_id_2')->label('Debit Account 2')->searchable()
                                    ->options(fn() => \App\Models\ChartOfAccount::all()
                                        ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                Select::make('credit_account_id_2')->label('Credit Account 2')->searchable()
                                    ->options(fn() => \App\Models\ChartOfAccount::all()
                                        ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                Select::make('debit_account_id_3')->label('Debit Account 3')->searchable()
                                    ->options(fn() => \App\Models\ChartOfAccount::all()
                                        ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                Select::make('credit_account_id_3')->label('Credit Account 3')->searchable()
                                    ->options(fn() => \App\Models\ChartOfAccount::all()
                                        ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                Select::make('debit_account_id_4')->label('Debit Account 4')->searchable()
                                    ->options(fn() => \App\Models\ChartOfAccount::all()
                                        ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                Select::make('credit_account_id_4')->label('Credit Account 4')->searchable()
                                    ->options(fn() => \App\Models\ChartOfAccount::all()
                                        ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                Select::make('debit_account_id_5')->label('Debit Account 5')->searchable()
                                    ->options(fn() => \App\Models\ChartOfAccount::all()
                                        ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                Select::make('credit_account_id_5')->label('Credit Account 5')->searchable()
                                    ->options(fn() => \App\Models\ChartOfAccount::all()
                                        ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                // Collapsible: Debit/Credit 6–10
                                Section::make('Additional Accounts (6–10)')->collapsible()->collapsed()->columns(2)
                                    ->schema([
                                        Select::make('debit_account_id_6')->label('Debit Account 6')->searchable()
                                            ->options(fn() => \App\Models\ChartOfAccount::all()
                                                ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                        Select::make('credit_account_id_6')->label('Credit Account 6')->searchable()
                                            ->options(fn() => \App\Models\ChartOfAccount::all()
                                                ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                        Select::make('debit_account_id_7')->label('Debit Account 7')->searchable()
                                            ->options(fn() => \App\Models\ChartOfAccount::all()
                                                ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                        Select::make('credit_account_id_7')->label('Credit Account 7')->searchable()
                                            ->options(fn() => \App\Models\ChartOfAccount::all()
                                                ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                        Select::make('debit_account_id_8')->label('Debit Account 8')->searchable()
                                            ->options(fn() => \App\Models\ChartOfAccount::all()
                                                ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                        Select::make('credit_account_id_8')->label('Credit Account 8')->searchable()
                                            ->options(fn() => \App\Models\ChartOfAccount::all()
                                                ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                        Select::make('debit_account_id_9')->label('Debit Account 9')->searchable()
                                            ->options(fn() => \App\Models\ChartOfAccount::all()
                                                ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                        Select::make('credit_account_id_9')->label('Credit Account 9')->searchable()
                                            ->options(fn() => \App\Models\ChartOfAccount::all()
                                                ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                        Select::make('debit_account_id_10')->label('Debit Account 10')->searchable()
                                            ->options(fn() => \App\Models\ChartOfAccount::all()
                                                ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),

                                        Select::make('credit_account_id_10')->label('Credit Account 10')->searchable()
                                            ->options(fn() => \App\Models\ChartOfAccount::all()
                                                ->mapWithKeys(fn($account) => [$account->id => "{$account->code} | {$account->name}"])->toArray()),
                                    ]),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->disableItemDeletion() 
                            ->minItems(1)
                            ->maxItems(1)
                            ->defaultItems(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_name')->searchable(),
                TextColumn::make('description')->limit(50),
                TextColumn::make('status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]); 
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactionSetups::route('/'),
            'edit' => Pages\EditTransactionSetup::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // disable add new setups
    }

    public static function canDelete($record): bool
    {
        return false; // disable delete setups
    }

    public static function canEdit($record): bool
    {
        return true; // can edit to manage accounts
    }
}
