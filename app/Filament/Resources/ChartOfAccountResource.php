<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChartOfAccountResource\Pages;
use App\Models\ChartOfAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use App\Models\VATControlAccount;

class ChartOfAccountResource extends Resource
{
    protected static ?string $model = ChartOfAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Accounting & Finance';
    protected static ?string $navigationLabel = 'Chart of Accounts';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Account Code')
                ->description('Unique identifier for the account.')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('Account Code')
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->maxLength(50),
                ])
                ->collapsible()
                ->collapsed(false),

            Forms\Components\Section::make('Basic Information')
                ->description('Enter the main details of the account.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Account Name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible(),
            
            Forms\Components\Section::make('Account Type & Statement Type')
                ->description('Select the accounting type, statement type, and sub-category if applicable.')
                ->schema([
                    // Account Type
                    Forms\Components\Select::make('account_type')
                        ->label('Account Type')
                        ->options([
                            'asset' => 'Asset',
                            'equity' => 'Equity',
                            'liability' => 'Liability',
                            'income' => 'Income',
                            'expense' => 'Expense',
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Auto-map account types → statement types
                            if (in_array($state, ['income', 'expense'])) {
                                $set('statement_type', 'income_statement');
                            } else {
                                $set('statement_type', 'balance_sheet');
                            }
                        })
                        ->required(),

                    // Sub-Category (Current / Non-Current)
                    Forms\Components\Select::make('sub_category')
                        ->label('Sub-Category')
                        ->options([
                            'current' => 'Current',
                            'non_current' => 'Non-Current',
                        ])
                        ->visible(fn ($get) => in_array($get('account_type'), ['asset', 'liability']))
                        ->required(fn ($get) => in_array($get('account_type'), ['asset', 'liability'])),
                        
                    // Statement Type (read-only, auto-set)
                    Forms\Components\Select::make('statement_type')
                        ->label('Statement Type')
                        ->options([
                            'balance_sheet' => 'Balance Sheet',
                            'income_statement' => 'Income Statement',
                        ])
                        ->reactive()
                        ->disabled()
                        ->required(), 
                ])
                ->columns(3)
                ->collapsible(),

            Forms\Components\Toggle::make('is_control_account')
                ->hidden(),


            Forms\Components\Section::make('Account Balances')
                ->description('Automatically maintained by the system. Read-only fields.')
                ->schema([
                    Forms\Components\TextInput::make('debit_total')
                        ->label('Debit Total')
                        ->numeric()
                        ->default(0.00)
                        ->disabled(),

                    Forms\Components\TextInput::make('credit_total')
                        ->label('Credit Total')
                        ->numeric()
                        ->default(0.00)
                        ->disabled(),

                    Forms\Components\TextInput::make('balance')
                        ->label('Balance (No VAT)')
                        ->numeric()
                        ->default(0.00)
                        ->disabled(),

                    Forms\Components\TextInput::make('balance_vat')
                        ->label('Balance (With VAT)')
                        ->numeric()
                        ->default(0.00)
                        ->disabled(),
                ])
                ->columns(2)
                ->collapsible(),
        ])->columns(2);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Account Name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('account_type')
                    ->label('Type')
                    ->colors([
                        'success' => 'asset',
                        'warning' => 'liability',
                        'info' => 'equity',
                        'danger' => 'expense',
                        'primary' => 'income',
                    ])
                    ->formatStateUsing(function ($state, $record) {
                        $type = ucfirst($state); 

                        if (in_array($state, ['asset', 'liability']) && $record->sub_category) {
                            $sub = str_replace('_', ' ', $record->sub_category); 
                            $type .= " ({$sub})";
                        }

                        return $type;
                    }),

                Tables\Columns\IconColumn::make('is_control_account')
                    ->label('Control')
                    ->boolean(),

                Tables\Columns\TextColumn::make('control_account_type')
                    ->label('Control Account Type')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('statement_type')
                    ->label('Statement Type')
                    ->formatStateUsing(fn(string $state): string => str($state)->replace('_', ' ')->title())
                    ->badge(),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance (LKR)')
                    ->money('LKR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_vat')
                    ->label('Balance (With VAT)')
                    ->money('LKR', true)
                    ->sortable(),

                ...(
                    Auth::user()->can('view audit columns')
                        ? [
                            Tables\Columns\TextColumn::make('created_by_user.name')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                            Tables\Columns\TextColumn::make('updated_by_user.name')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                            Tables\Columns\TextColumn::make('created_at')->label('Created At')->dateTime()->toggleable(isToggledHiddenByDefault: true)->sortable(),
                            Tables\Columns\TextColumn::make('updated_at')->label('Updated At')->dateTime()->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        ]
                        : []
                ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statement_type')
                    ->label('Statement Type')
                    ->options([
                        'balance_sheet' => 'Balance Sheet',
                        'income_statement' => 'Income Statement',
                    ]),

                Tables\Filters\SelectFilter::make('account_type')
                    ->label('Account Type')
                    ->options([
                        'asset' => 'Asset',
                        'equity' => 'Equity',
                        'liability' => 'Liability',
                        'income' => 'Income',
                        'expense' => 'Expense',
                    ]),

                Tables\Filters\TernaryFilter::make('is_control_account')
                    ->label('Control Accounts Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->is_control_account || $record->status === 'active')
                    ->before(function ($record, $action) {
                        if ($record->is_control_account) {
                            $action->cancel();

                            \Filament\Notifications\Notification::make()
                                ->title('Cannot delete control account')
                                ->body("The account '{$record->name}' is a control account and cannot be deleted.")
                                ->danger()
                                ->send();

                            return;
                        }

                        // Prevent deleting active accounts
                        if ($record->status === 'active') {
                            $action->cancel();

                            \Filament\Notifications\Notification::make()
                                ->title('Cannot delete active account')
                                ->body("The account '{$record->name}' is currently active and cannot be deleted.")
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->emptyStateHeading('No Chart of Accounts found')
            ->emptyStateDescription('Start by creating a new chart of account.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChartOfAccounts::route('/'),
            'create' => Pages\CreateChartOfAccount::route('/create'),
            'edit'   => Pages\EditChartOfAccount::route('/{record}/edit'),

            'balance-sheet'     => Pages\BalanceSheet::route('/balance-sheet'),
            'income-statement'  => Pages\IncomeStatement::route('/income-statement'),
        ];
    }
}
