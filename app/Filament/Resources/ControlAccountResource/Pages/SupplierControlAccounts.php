<?php

namespace App\Filament\Resources\ControlAccountResource\Pages;

use App\Filament\Resources\ControlAccountResource;
use App\Models\SupplierControlAccount;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class SupplierControlAccounts extends ListRecords
{
    protected static string $resource = ControlAccountResource::class;

    protected static ?string $title = 'Supplier Control Accounts';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('customer')
                ->label('Customer Control Account')
                ->color('success')
                ->icon('heroicon-o-user-group')
                ->url(route('filament.admin.resources.control-accounts.customer')),

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
            ->query(SupplierControlAccount::query())
            ->columns([
                Tables\Columns\TextColumn::make('supplier.supplier_id')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Supplier ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('debit_total')
                    ->label('Debit Total')
                    ->money('LKR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('credit_total')
                    ->label('Credit Total')
                    ->money('LKR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance (no VAT)')
                    ->money('LKR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_vat')
                    ->label('Balance (with VAT)')
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
                // 🔹 View Details
                Tables\Actions\Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Supplier Control Account Details')
                    ->modalSubmitAction(false)
                    ->modalWidth('lg')
                    ->modalContent(function (SupplierControlAccount $record) {
                        return new HtmlString('
                            <div class="space-y-4">
                                <div>
                                    <h3 class="font-semibold text-gray-700">Supplier Info</h3>
                                    <p><strong>ID:</strong> ' . e($record->supplier?->supplier_id ?? 'N/A') . '</p>
                                    <p><strong>Name:</strong> ' . e($record->supplier?->name ?? 'N/A') . '</p>
                                </div>

                                <div>
                                    <h3 class="font-semibold text-gray-700">Accounts</h3>
                                    <p><strong>Payable:</strong> ' . e($record->payableAccount?->account_name ?? 'N/A') . '</p>
                                    <p><strong>Purchase:</strong> ' . e($record->purchaseAccount?->account_name ?? 'N/A') . '</p>
                                    <p><strong>VAT Input:</strong> ' . e($record->vatInputAccount?->account_name ?? 'N/A') . '</p>
                                    <p><strong>Purchase Discount:</strong> ' . e($record->purchaseDiscountAccount?->account_name ?? 'N/A') . '</p>
                                    <p><strong>Bad Debt Recovery:</strong> ' . e($record->badDebtRecoveryAccount?->account_name ?? 'N/A') . '</p>
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

                // 🔹 Edit Accounts
                Tables\Actions\Action::make('edit')
                    ->label('Edit Accounts')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->fillForm(fn(SupplierControlAccount $record) => [
                        'payable_account_id' => $record->payable_account_id,
                        'purchase_account_id' => $record->purchase_account_id,
                        'vat_input_account_id' => $record->vat_input_account_id,
                        'purchase_discount_account_id' => $record->purchase_discount_account_id,
                        'bad_debt_recovery_account_id' => $record->bad_debt_recovery_account_id,
                    ])
                    ->form([
                        // -----------------------------
                        // Core Payables
                        // -----------------------------
                        \Filament\Forms\Components\Section::make('Core Payables')
                            ->schema([
                                \Filament\Forms\Components\Select::make('payable_account_id')
                                    ->label('Payable Account')
                                    ->relationship('payableAccount', 'name')
                                    ->searchable()
                                    ->required(),

                                \Filament\Forms\Components\Select::make('supplier_advance_account_id')
                                    ->label('Supplier Advance Account')
                                    ->relationship('supplierAdvanceAccount', 'name')
                                    ->searchable(),
                            ])->columns(2),

                        // -----------------------------
                        // Purchase Related Accounts
                        // -----------------------------
                        \Filament\Forms\Components\Section::make('Purchase Accounts')
                            ->schema([
                                \Filament\Forms\Components\Select::make('purchase_account_id')
                                    ->label('Purchase Account')
                                    ->relationship('purchaseAccount', 'name')
                                    ->searchable()
                                    ->required(),

                                \Filament\Forms\Components\Select::make('purchase_return_account_id')
                                    ->label('Purchase Return Account')
                                    ->relationship('purchaseReturnAccount', 'name')
                                    ->searchable(),

                                \Filament\Forms\Components\Select::make('purchase_discount_account_id')
                                    ->label('Purchase Discount Account')
                                    ->relationship('purchaseDiscountAccount', 'name')
                                    ->searchable(),

                                \Filament\Forms\Components\Select::make('freight_in_account_id')
                                    ->label('Freight In Account')
                                    ->relationship('freightInAccount', 'name')
                                    ->searchable(),

                                \Filament\Forms\Components\Select::make('grni_account_id')
                                    ->label('GRNI Account')
                                    ->relationship('grniAccount', 'name')
                                    ->searchable(),
                            ])->columns(2),

                        // -----------------------------
                        // VAT / Tax Accounts
                        // -----------------------------
                        \Filament\Forms\Components\Section::make('VAT / Tax Accounts')
                            ->schema([
                                \Filament\Forms\Components\Select::make('vat_input_account_id')
                                    ->label('VAT Input Account')
                                    ->relationship('vatInputAccount', 'name')
                                    ->searchable(),

                                \Filament\Forms\Components\Select::make('vat_suspense_account_id')
                                    ->label('VAT Suspense Account')
                                    ->relationship('vatSuspenseAccount', 'name')
                                    ->searchable(),
                            ])->columns(2),

                        // -----------------------------
                        // Manufacturing Specific Accounts
                        // -----------------------------
                        \Filament\Forms\Components\Section::make('Manufacturing Accounts')
                            ->schema([
                                \Filament\Forms\Components\Select::make('direct_material_purchase_account_id')
                                    ->label('Direct Material Purchase Account')
                                    ->relationship('directMaterialPurchaseAccount', 'name')
                                    ->searchable(),

                                \Filament\Forms\Components\Select::make('indirect_material_purchase_account_id')
                                    ->label('Indirect Material Purchase Account')
                                    ->relationship('indirectMaterialPurchaseAccount', 'name')
                                    ->searchable(),

                                \Filament\Forms\Components\Select::make('production_supplies_account_id')
                                    ->label('Production Supplies Account')
                                    ->relationship('productionSuppliesAccount', 'name')
                                    ->searchable(),

                                \Filament\Forms\Components\Select::make('subcontracting_expense_account_id')
                                    ->label('Subcontracting Expense Account')
                                    ->relationship('subcontractingExpenseAccount', 'name')
                                    ->searchable(),
                            ])->columns(2),

                        // -----------------------------
                        // Adjustments / Write-offs
                        // -----------------------------
                        \Filament\Forms\Components\Section::make('Adjustments / Write-offs')
                            ->schema([
                                \Filament\Forms\Components\Select::make('bad_debt_recovery_account_id')
                                    ->label('Bad Debt Recovery Account')
                                    ->relationship('badDebtRecoveryAccount', 'name')
                                    ->searchable(),

                                \Filament\Forms\Components\Select::make('supplier_writeoff_account_id')
                                    ->label('Supplier Write-off Account')
                                    ->relationship('supplierWriteoffAccount', 'name')
                                    ->searchable(),

                                \Filament\Forms\Components\Select::make('purchase_price_variance_account_id')
                                    ->label('Purchase Price Variance Account')
                                    ->relationship('purchasePriceVarianceAccount', 'name')
                                    ->searchable(),
                            ])->columns(2),
                    ])

                    ->action(function (array $data, SupplierControlAccount $record): void {
                        $record->update([
                            'payable_account_id' => $data['payable_account_id'],
                            'purchase_account_id' => $data['purchase_account_id'],
                            'vat_input_account_id' => $data['vat_input_account_id'],
                            'purchase_discount_account_id' => $data['purchase_discount_account_id'],
                            'bad_debt_recovery_account_id' => $data['bad_debt_recovery_account_id'],
                            'updated_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Supplier Control Account Updated')
                            ->body('The control account for ' . $record->supplier?->name . ' has been successfully updated.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No Supplier Control Accounts')
            ->emptyStateDescription('There are currently no suppliers in the control accounts. Add a supplier to get started.')
            ->emptyStateIcon('heroicon-o-exclamation-circle');
    }
}
