<?php

namespace App\Filament\Resources\ControlAccountResource\Pages;

use App\Filament\Resources\ControlAccountResource;
use App\Models\CustomerControlAccount;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Actions;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;


class CustomerControlAccounts extends ListRecords
{
    protected static string $resource = ControlAccountResource::class;

    protected static ?string $title = 'Customer Control Accounts';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('supplier')
                ->label('Supplier Control Account')
                ->color('warning')
                ->icon('heroicon-o-truck')
                ->url(route('filament.admin.resources.control-accounts.supplier')),

            Actions\Action::make('vat')
                ->label('VAT Control Account')
                ->color('info')
                ->icon('heroicon-o-banknotes')
                ->url(route('filament.admin.resources.control-accounts.vat')),
                
            Actions\Action::make('back')
                ->label('Back to Control Accounts')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ControlAccountResource::getUrl('index')),
        ];
    }

    public function table(Table $table): Table
{
    return $table
        ->query(CustomerControlAccount::query())
        ->columns([
            Tables\Columns\TextColumn::make('customer.customer_id')
                ->label('Customer ID')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('customer.name')
                ->toggleable(isToggledHiddenByDefault: true)
                ->label('Customer Name')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('debit_total')
                ->label('Debit Balance')
                ->money('LKR', true)
                ->sortable(),

            Tables\Columns\TextColumn::make('credit_total')
                ->label('Credit Balance')
                ->money('LKR', true)
                ->sortable(),

            Tables\Columns\TextColumn::make('balance')
                ->label('Balance without VAT')
                ->money('LKR', true)
                ->sortable(),

            Tables\Columns\TextColumn::make('balance_vat')
                ->label('Balance with VAT')
                ->money('LKR', true)
                ->sortable(),

            Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->toggleable(isToggledHiddenByDefault: true),
            
            ...(
                Auth::user()->can('view audit columns')
                    ? [
                        Tables\Columns\TextColumn::make('created_by')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        Tables\Columns\TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        Tables\Columns\TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                        Tables\Columns\TextColumn::make('updated_at')->label('Updated At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                    ]
                    : []
                    ),
        ])
        ->actions([
            // 🔹 View details modal
            Tables\Actions\Action::make('view_details')
                ->label('View Details')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->modalHeading('Customer Control Account Details')
                ->modalSubmitAction(false)
                ->modalWidth('lg')
                ->modalContent(function (CustomerControlAccount $record) {
                    return new HtmlString('
                        <div class="space-y-4">
                            <div>
                                <h3 class="font-semibold text-gray-700">Customer Info</h3>
                                <p><strong>ID:</strong> ' . e($record->customer?->customer_id ?? 'N/A') . '</p>
                                <p><strong>Name:</strong> ' . e($record->customer?->name ?? 'N/A') . '</p>
                            </div>

                            <div>
                                <h3 class="font-semibold text-gray-700">Accounts</h3>
                                <p><strong>Receivable:</strong> ' . e($record->receivableAccount?->account_name ?? 'N/A') . '</p>
                                <p><strong>Sales:</strong> ' . e($record->salesAccount?->account_name ?? 'N/A') . '</p>
                                <p><strong>VAT Output:</strong> ' . e($record->vatOutputAccount?->account_name ?? 'N/A') . '</p>
                                <p><strong>Bad Debt:</strong> ' . e($record->badDebtExpenseAccount?->account_name ?? 'N/A') . '</p>
                            </div>

                            <div>
                                <h3 class="font-semibold text-gray-700">Balances</h3>
                                <p><strong>Debit Total:</strong> LKR ' . number_format($record->debit_total, 2) . '</p>
                                <p><strong>Credit Total:</strong> LKR ' . number_format($record->credit_total, 2) . '</p>
                                <p><strong>Balance (no VAT):</strong> LKR ' . number_format($record->balance, 2) . '</p>
                                <p><strong>Balance (with VAT):</strong> LKR ' . number_format($record->balance_vat, 2) . '</p>
                            </div>

                            <div>
                                <h3 class="font-semibold text-gray-700">Audit</h3>
                                <p><strong>Created At:</strong> ' . e(optional($record->created_at)->format("Y-m-d H:i")) . '</p>
                                <p><strong>Updated At:</strong> ' . e(optional($record->updated_at)->format("Y-m-d H:i")) . '</p>
                            </div>
                        </div>
                    ');
                }),

            // 🔹 Edit modal action
            Tables\Actions\Action::make('edit')
                ->label('Edit Accounts')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->fillForm(fn(CustomerControlAccount $record) => [
                    'receivable_account_id' => $record->receivable_account_id,
                    'sales_account_id' => $record->sales_account_id,
                    'vat_output_account_id' => $record->vat_output_account_id,
                    'bad_debt_expense_account_id' => $record->bad_debt_expense_account_id,
                ])
                ->form([
                    \Filament\Forms\Components\Select::make('receivable_account_id')
                        ->label('Receivable Account')
                        ->relationship('receivableAccount', 'account_name')
                        ->searchable()
                        ->required(),

                    \Filament\Forms\Components\Select::make('sales_account_id')
                        ->label('Sales Account')
                        ->relationship('salesAccount', 'account_name')
                        ->searchable()
                        ->required(),

                    \Filament\Forms\Components\Select::make('vat_output_account_id')
                        ->label('VAT Output Account')
                        ->relationship('vatOutputAccount', 'account_name')
                        ->searchable()
                        ->required(),

                    \Filament\Forms\Components\Select::make('bad_debt_expense_account_id')
                        ->label('Bad Debt Expense Account')
                        ->relationship('badDebtExpenseAccount', 'account_name')
                        ->searchable(),
                ])
                ->action(function (array $data, CustomerControlAccount $record): void {
                    $record->update([
                        'receivable_account_id' => $data['receivable_account_id'],
                        'sales_account_id' => $data['sales_account_id'],
                        'vat_output_account_id' => $data['vat_output_account_id'],
                        'bad_debt_expense_account_id' => $data['bad_debt_expense_account_id'],
                        'updated_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Control Account Updated')
                        ->body('The control account for ' . $record->customer?->name . ' has been successfully updated.')
                        ->success()
                        ->send();
                }),
        ])
        ->emptyStateHeading('No Customers in the control account')
        ->emptyStateDescription('There are currently no customers available. Please add one customer to get started.')
        ->emptyStateIcon('heroicon-o-exclamation-circle');
}

}

