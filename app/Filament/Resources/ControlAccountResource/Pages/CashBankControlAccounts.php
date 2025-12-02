<?php

namespace App\Filament\Resources\ControlAccountResource\Pages;

use App\Filament\Resources\ControlAccountResource;
use App\Models\CashBankControlAccount;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Filament\Forms;

class CashBankControlAccounts extends ListRecords
{
    protected static string $resource = ControlAccountResource::class;
    protected static ?string $title = 'Cash & Bank Control Accounts';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\ControlAccountResource\Widgets\ControlAccountButtons::class,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CashBankControlAccount::query())
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Account Name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('bank_name')->label('Bank Name')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('account_number')->label('Account Number')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('debit_balance')->label('Debit Balance')->money('LKR', true)->sortable(),
                Tables\Columns\TextColumn::make('credit_balance')->label('Credit Balance')->money('LKR', true)->sortable(),
                Tables\Columns\TextColumn::make('balance')->label('Balance')->money('LKR', true)->sortable(),
                Tables\Columns\BadgeColumn::make('is_active')
                    ->label('Active')
                    ->colors([
                        'success' => fn($state) => $state,
                        'danger' => fn($state) => !$state,
                    ]),
            ])
            ->headerActions([
                Action::make('add')
                    ->label('Add Account')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Section::make('General Information')
                            ->schema([
                                Forms\Components\TextInput::make('code')->label('Code')->required(),
                                Forms\Components\TextInput::make('name')->label('Account Name')->required(),
                                Forms\Components\Select::make('account_type')
                                    ->label('Type')
                                    ->options([
                                        'cash' => 'Cash',
                                        'bank' => 'Bank',
                                        'petty_cash' => 'Petty Cash',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('currency')->label('Currency')->default('LKR'),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Bank Details')
                            ->schema([
                                Forms\Components\TextInput::make('bank_name')->label('Bank Name')->placeholder('Required if account type is Bank'),
                                Forms\Components\TextInput::make('branch_name')->label('Branch Name'),
                                Forms\Components\TextInput::make('account_number')->label('Account Number'),
                                Forms\Components\TextInput::make('swift_code')->label('SWIFT Code'),
                                Forms\Components\TextInput::make('iban')->label('IBAN'),
                                Forms\Components\TextInput::make('bank_address')->label('Bank Address'),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Opening & Balances')
                            ->schema([
                                Forms\Components\TextInput::make('opening_balance')->label('Opening Balance')->numeric()->default(0),
                                Forms\Components\DatePicker::make('opening_balance_date')->label('Opening Balance Date')->default(now())->required()->maxDate(now()),
                                Forms\Components\TextInput::make('debit_total_vat')->label('Debit Total VAT')->numeric()->default(0),
                                Forms\Components\TextInput::make('credit_total_vat')->label('Credit Total VAT')->numeric()->default(0),
                                Forms\Components\TextInput::make('total_debit')->label('Total Debit')->numeric()->default(0),
                                Forms\Components\TextInput::make('total_credit')->label('Total Credit')->numeric()->default(0),
                                Forms\Components\TextInput::make('balance_vat')->label('Balance VAT')->numeric()->default(0),
                                Forms\Components\TextInput::make('current_balance')->label('Current Balance')->numeric()->default(0),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Additional Information')
                            ->schema([
                                Forms\Components\TextInput::make('tax_number')->label('Tax Number'),
                                Forms\Components\Textarea::make('notes')->label('Notes'),
                            ])
                            ->columns(1),
                    ])
                    ->action(function (array $data, Forms\Form $form) {
                        // Check for duplicate code
                        if (CashBankControlAccount::where('code', $data['code'])->exists()) {
                            Notification::make()
                                ->title('Duplicate Code')
                                ->body('An account with this code already exists.')
                                ->danger()
                                ->send();

                            // Clear the code field in the form
                            $form->fill([
                                'code' => '',
                            ]);

                            return; // stop execution
                        }

                        // Create account if no duplicate
                        $account = CashBankControlAccount::create($data);

                        Notification::make()
                            ->title('Account Created')
                            ->body("Account {$account->name} has been successfully created.")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                // ───────────────────────────────
                // VIEW ACTION
                // ───────────────────────────────
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn(CashBankControlAccount $record) => "Cash/Bank Account: {$record->name}")
                    ->modalContent(fn(CashBankControlAccount $record) => new HtmlString("
                        <div class='space-y-2'>
                            <p><strong>Code:</strong> {$record->code}</p>
                            <p><strong>Name:</strong> {$record->name}</p>
                            <p><strong>Type:</strong> {$record->account_type}</p>
                            <p><strong>Bank Name:</strong> {$record->bank_name}</p>
                            <p><strong>Account Number:</strong> {$record->account_number}</p>
                            <p><strong>Debit Total (VAT):</strong> LKR {$record->debit_total_vat}</p>
                            <p><strong>Credit Total (VAT):</strong> LKR {$record->credit_total_vat}</p>
                            <p><strong>Balance (VAT):</strong> LKR {$record->balance_vat}</p>
                            <p><strong>Active:</strong> " . ($record->is_active ? 'Yes' : 'No') . "</p>
                        </div>
                    ")),

                // ───────────────────────────────
                // EDIT ACTION
                // ───────────────────────────────
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn(CashBankControlAccount $record) => $record->status === 'created') // optional: only editable if status = created
                    ->form([
                        Forms\Components\Section::make('General Information')
                            ->schema([
                                Forms\Components\TextInput::make('code')->label('Code')->required(),
                                Forms\Components\TextInput::make('name')->label('Account Name')->required(),
                                Forms\Components\Select::make('account_type')
                                    ->label('Type')
                                    ->options([
                                        'cash' => 'Cash',
                                        'bank' => 'Bank',
                                        'petty_cash' => 'Petty Cash',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('currency')->label('Currency')->default('LKR'),
                                Forms\Components\Toggle::make('is_active')->label('Active'),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Bank Details')
                            ->schema([
                                Forms\Components\TextInput::make('bank_name')->label('Bank Name'),
                                Forms\Components\TextInput::make('branch_name')->label('Branch Name'),
                                Forms\Components\TextInput::make('account_number')->label('Account Number'),
                                Forms\Components\TextInput::make('swift_code')->label('SWIFT Code'),
                                Forms\Components\TextInput::make('iban')->label('IBAN'),
                                Forms\Components\TextInput::make('bank_address')->label('Bank Address'),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Opening & Balances')
                            ->schema([
                                Forms\Components\TextInput::make('opening_balance')->label('Opening Balance')->numeric(),
                                Forms\Components\DatePicker::make('opening_balance_date')->label('Opening Balance Date')->maxDate(now()),
                                Forms\Components\TextInput::make('debit_balance')->label('Debit Balance')->numeric(),
                                Forms\Components\TextInput::make('credit_balance')->label('Credit Balance')->numeric(),
                                Forms\Components\TextInput::make('balance')->label('Balance')->numeric(),
                            ])
                            ->columns(2),

                        Forms\Components\Section::make('Additional Information')
                            ->schema([
                                Forms\Components\TextInput::make('tax_number')->label('Tax Number'),
                                Forms\Components\Textarea::make('notes')->label('Notes'),
                            ])
                            ->columns(1),
                    ])
                    ->action(function (array $data, CashBankControlAccount $record) {
                        $record->update($data);
                        Notification::make()
                            ->title('Updated')
                            ->body("Account {$record->name} has been updated.")
                            ->success()
                            ->send();
                    }),

                // ───────────────────────────────
                // DELETE ACTION
                // ───────────────────────────────
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(function (CashBankControlAccount $record) {
                        $record->delete();
                        Notification::make()
                            ->title('Deleted')
                            ->body("Account {$record->name} has been deleted.")
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No Cash/Bank Accounts')
            ->emptyStateDescription('Please add a cash, bank, or petty cash account to get started.')
            ->emptyStateIcon('heroicon-o-exclamation-circle');
    }
}
