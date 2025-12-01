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
use Filament\Forms;

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
                Tables\Columns\TextColumn::make('customer.customer_id')->label('Customer ID')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer Name')->sortable()->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('debit_total')->label('Debit Total')->money('LKR', true)->sortable(),
                Tables\Columns\TextColumn::make('credit_total')->label('Credit Total')->money('LKR', true)->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('balance')->label('Balance (No VAT)')->money('LKR', true)->sortable(),
                Tables\Columns\TextColumn::make('balance_vat')->label('Balance (With VAT)')->money('LKR', true)->sortable(),

                ...(Auth::user()->can('view audit columns') ? [
                    Tables\Columns\TextColumn::make('created_by')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                    Tables\Columns\TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                    Tables\Columns\TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                    Tables\Columns\TextColumn::make('updated_at')->label('Updated At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                ] : []),
            ])
            ->actions([
                // View Details
                Tables\Actions\Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Customer Control Account Details')
                    ->modalSubmitAction(false)
                    ->modalWidth('lg')
                    ->modalContent(function (CustomerControlAccount $record) {
                        $accounts = [
                            'Receivable' => $record->receivableAccount?->name,
                            'Sales' => $record->salesAccount?->name,
                            'Export Sales' => $record->exportSalesAccount?->name,
                            'Sales Returns' => $record->salesReturnAccount?->name,
                            'Sales Discounts' => $record->salesDiscountAccount?->name,
                            'Customer Advances' => $record->customerAdvanceAccount?->name,
                            'VAT Output' => $record->vatOutputAccount?->name,
                            'VAT Receivable' => $record->vatReceivableAccount?->name,
                            'Bad Debt' => $record->badDebtExpenseAccount?->name,
                            'Allowance Doubtful' => $record->allowanceDoubtfulAccount?->name,
                            'Cash Account' => $record->cashAccount?->name,
                            'Bank Account' => $record->bankAccount?->name,
                            'Undeposited Funds' => $record->undepositedFundsAccount?->name,
                            'Inventory' => $record->inventoryAccount?->name,
                            'COGS' => $record->cogsAccount?->name,
                        ];

                        $html = '<div class="space-y-4"><div><h3 class="font-semibold text-gray-700">Customer Info</h3>';
                        $html .= '<p><strong>ID:</strong> ' . e($record->customer?->customer_id ?? 'N/A') . '</p>';
                        $html .= '<p><strong>Name:</strong> ' . e($record->customer?->name ?? 'N/A') . '</p></div>';
                        $html .= '<div><h3 class="font-semibold text-gray-700">Accounts</h3>';
                        foreach ($accounts as $label => $value) {
                            $html .= '<p><strong>' . $label . ':</strong> ' . e($value ?? 'N/A') . '</p>';
                        }
                        $html .= '</div><div><h3 class="font-semibold text-gray-700">Balances</h3>';
                        $html .= '<p><strong>Debit Total:</strong> LKR ' . number_format($record->debit_total, 2) . '</p>';
                        $html .= '<p><strong>Credit Total:</strong> LKR ' . number_format($record->credit_total, 2) . '</p>';
                        $html .= '<p><strong>Balance (No VAT):</strong> LKR ' . number_format($record->balance, 2) . '</p>';
                        $html .= '<p><strong>Balance (With VAT):</strong> LKR ' . number_format($record->balance_vat, 2) . '</p></div>';
                        $html .= '<div><h3 class="font-semibold text-gray-700">Audit</h3>';
                        $html .= '<p><strong>Created At:</strong> ' . e(optional($record->created_at)->format("Y-m-d H:i")) . '</p>';
                        $html .= '<p><strong>Updated At:</strong> ' . e(optional($record->updated_at)->format("Y-m-d H:i")) . '</p></div></div>';

                        return new HtmlString($html);
                    }),

                // Edit Accounts
                Tables\Actions\Action::make('edit')
                    ->label('Edit Accounts')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->fillForm(fn(CustomerControlAccount $record) => [
                        'receivable_account_id' => $record->receivable_account_id,
                        'sales_account_id' => $record->sales_account_id,
                        'export_sales_account_id' => $record->export_sales_account_id,
                        'sales_return_account_id' => $record->sales_return_account_id,
                        'sales_discount_account_id' => $record->sales_discount_account_id,
                        'customer_advance_account_id' => $record->customer_advance_account_id,
                        'vat_output_account_id' => $record->vat_output_account_id,
                        'vat_receivable_account_id' => $record->vat_receivable_account_id,
                        'bad_debt_expense_account_id' => $record->bad_debt_expense_account_id,
                        'allowance_for_doubtful_account_id' => $record->allowance_for_doubtful_account_id,
                        'cash_account_id' => $record->cash_account_id,
                        'bank_account_id' => $record->bank_account_id,
                        'undeposited_funds_account_id' => $record->undeposited_funds_account_id,
                        'inventory_account_id' => $record->inventory_account_id,
                        'cogs_account_id' => $record->cogs_account_id,
                    ])
                    ->form([
                        // --- Main Accounts ---
                        Forms\Components\Section::make('Main Accounts')
                            ->schema([
                                Forms\Components\Select::make('receivable_account_id')
                                    ->label('Receivable Account')
                                    ->relationship(
                                        'receivableAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->where('control_account_type', 'customer')
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload()
                                    ->required(),
                                    
                                Forms\Components\Select::make('sales_account_id')
                                    ->label('Sales Account')
                                    ->relationship(
                                        'salesAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->where('control_account_type', 'customer')
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload()
                                    ->required(),
                                    
                                Forms\Components\Select::make('export_sales_account_id')
                                    ->label('Export Sales Account')
                                    ->relationship(
                                        'exportSalesAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload(),
                            ])->columns(2),

                        // --- Contra & Advances ---
                        Forms\Components\Section::make('Contra & Advances')
                            ->schema([
                                Forms\Components\Select::make('sales_return_account_id')
                                    ->label('Sales Returns Account')
                                    ->relationship(
                                        'salesReturnAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload(),
                                    
                                Forms\Components\Select::make('sales_discount_account_id')
                                    ->label('Sales Discounts Allowed')
                                    ->relationship(
                                        'salesDiscountAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload(),
                                    
                                Forms\Components\Select::make('customer_advance_account_id')
                                    ->label('Customer Advances')
                                    ->relationship(
                                        'customerAdvanceAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload(),
                            ])->columns(2),

                        // --- VAT Accounts ---
                        Forms\Components\Section::make('VAT Accounts')
                            ->schema([
                                Forms\Components\Select::make('vat_output_account_id')
                                    ->label('VAT Output Account')
                                    ->relationship(
                                        'vatOutputAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload()
                                    ->required(),
                                    
                                Forms\Components\Select::make('vat_receivable_account_id')
                                    ->label('VAT Receivable Account')
                                    ->relationship(
                                        'vatReceivableAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload(),
                            ])->columns(2),

                        // --- Payment Accounts ---
                        Forms\Components\Section::make('Payment Accounts')
                            ->schema([
                                Forms\Components\Select::make('cash_account_id')
                                    ->label('Cash Account')
                                    ->relationship(
                                        'cashAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload(),
                                    
                                Forms\Components\Select::make('bank_account_id')
                                    ->label('Bank Account')
                                    ->relationship(
                                        'bankAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload(),
                                    
                                Forms\Components\Select::make('undeposited_funds_account_id')
                                    ->label('Undeposited Funds Account')
                                    ->relationship(
                                        'undepositedFundsAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload(),
                            ])->columns(2),

                        // --- Expenses & Allowances ---
                        Forms\Components\Section::make('Expenses & Allowances')
                            ->schema([
                                Forms\Components\Select::make('bad_debt_expense_account_id')
                                    ->label('Bad Debt Expense Account')
                                    ->relationship(
                                        'badDebtExpenseAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload(),
                                    
                                Forms\Components\Select::make('allowance_for_doubtful_account_id')
                                    ->label('Allowance for Doubtful Debts')
                                    ->relationship(
                                        'allowanceDoubtfulAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload(),
                            ])->columns(2),

                        // --- Inventory & COGS ---
                        Forms\Components\Section::make('Inventory & COGS')
                            ->schema([
                                Forms\Components\Select::make('inventory_account_id')
                                    ->label('Finished Goods Inventory')
                                    ->relationship(
                                        'inventoryAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload(),
                                    
                                Forms\Components\Select::make('cogs_account_id')
                                    ->label('COGS Account')
                                    ->relationship(
                                        'cogsAccount',
                                        'name',
                                        fn($query) => $query
                                            ->where('is_control_account', true)
                                            ->orderBy('code')
                                    )
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} | {$record->name}")
                                    ->searchable(['code', 'name'])
                                    ->preload(),
                            ])->columns(2),
                    ])
                    ->action(function (array $data, CustomerControlAccount $record) {
                        $record->update(array_merge($data, ['updated_by' => auth()->id()]));
                        Notification::make()
                            ->title('Control Account Updated')
                            ->body('The control account for ' . $record->customer?->name . ' has been successfully updated.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No Customers in the control account')
            ->emptyStateDescription('Please add a customer to get started.')
            ->emptyStateIcon('heroicon-o-exclamation-circle');
    }
}