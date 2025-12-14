<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierAdvanceInvoiceResource\Pages;
use App\Models\SupplierAdvanceInvoice;
use App\Models\PurchaseOrder;
use App\Models\SuppAdvInvoicePayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextArea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action; 
use Filament\Support\Enums\Js;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use App\Models\PurchaseOrderInvoice;

class SupplierAdvanceInvoiceResource extends Resource
{
    protected static ?string $model = SupplierAdvanceInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'PO Invoices';
    protected static ?string $label = 'PO Advance Invoice';
    protected static ?string $pluralLabel = 'PO Advance Invoices';
    protected static ?string $navigationLabel = 'PO Advance Invoices';
    protected static ?int $navigationSort = 7;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('view supplier advance invoices') ?? false;
    }
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Purchase Order Details')
                        ->schema([
                            Section::make('Purchase Order Information')
                                ->columns(2)
                                ->schema([
                                    Select::make('purchase_order_id')
                                        ->label('Purchase Order')
                                        ->required()
                                        ->dehydrated()
                                        ->disabled(fn (?string $context) => $context === 'edit')
                                        ->searchable()
                                        ->options(function () {
                                            return \App\Models\PurchaseOrder::query()
                                                ->where('status', '!=', 'closed') 
                                                ->get()
                                                ->mapWithKeys(fn ($order) => [
                                                    $order->id => "ID:{$order->id} | Total: Rs. " . number_format($order->grand_total, 2) . 
                                                                " | Remaining: Rs. " . number_format($order->remaining_balance, 2)
                                                ]);
                                        })
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, $set) {
                                            $purchaseOrder = \App\Models\PurchaseOrder::with('supplier')->find($state);

                                            if ($purchaseOrder) {
                                                $supplier = $purchaseOrder->supplier;

                                                $set('supplier_id', $supplier?->supplier_id ?? null);
                                                $set('supplier_name', $supplier?->name ?? 'Unknown');
                                                $set('supplier_phone', $supplier?->phone_1 ?? null);
                                                $set('supplier_email', $supplier?->email ?? null);

                                                $set('wanted_date', $purchaseOrder->wanted_date ?? null);
                                                $set('remaining_balance', $purchaseOrder->remaining_balance ?? 0);
                                                $set('status', $purchaseOrder->status ?? null);
                                                $set('purchase_order_items', $purchaseOrder->items?->toArray() ?? []);
                                            } else {
                                                $set('supplier_id', null);
                                                $set('supplier_name', 'Unknown');
                                                $set('supplier_phone', null);
                                                $set('supplier_email', null);
                                                $set('wanted_date', null);
                                                $set('remaining_balance', 0);
                                                $set('status', null);
                                                $set('purchase_order_items', []);
                                            }
                                        }),

                                    TextInput::make('supplier_id')
                                        ->label('Supplier ID')
                                        ->disabled()
                                        ->formatStateUsing(fn($state) => str_pad($state ?? 0, 5, '0', STR_PAD_LEFT))
                                        ->dehydrated(),

                                    TextInput::make('supplier_name')
                                        ->label('Supplier Name')
                                        ->disabled(),

                                    TextInput::make('supplier_phone')
                                        ->label('Supplier Phone')
                                        ->disabled(),

                                    TextInput::make('supplier_email')
                                        ->label('Supplier Email')
                                        ->disabled(),
                                ]),

                            Section::make('Purchase Order Items')
                                ->schema([
                                    Repeater::make('purchase_order_items')
                                        ->columns(5)
                                        ->schema([
                                            TextInput::make('inventory_item_id')->label('Inventory Item ID')->disabled(),
                                            TextInput::make('quantity')->label('Quantity')->disabled(),
                                            TextInput::make('price')->label('Price')->disabled(),
                                            TextInput::make('arrived_quantity')->label('Arrived Quantity')->disabled(),
                                            TextInput::make('total_sale')->label('Total Sale')->disabled(),
                                        ])
                                        ->disabled(),          
                                        
                                        Placeholder::make('grand_total')
                                            ->label('Grand Total')
                                            ->content(function (Get $get) {
                                                $items = $get('purchase_order_items') ?? [];
                                                $sum = collect($items)->sum('total_sale');
                                                return 'Rs. ' . number_format((float) $sum, 2);
                                            }),

                                        Hidden::make('grand_total')
                                            ->dehydrated(),
                                ]),
                        ]),

                    Tabs\Tab::make('Payment for Purchase Order Items')
                        ->schema([
                            Section::make('Grand Total of Purchase Order Items')
                                ->columns(2)
                                ->schema([
                                    Placeholder::make('grand_total')
                                        ->label('Grand Total')
                                        ->disabled()
                                        ->content(function (Get $get) {
                                            $items = $get('purchase_order_items') ?? [];
                                            $sum = collect($items)->sum('total_sale');
                                            return 'Rs. ' . number_format((float) $sum, 2);
                                        }),

                                    Placeholder::make('remaining_balance')
                                        ->label('Remaining Balance')
                                        ->disabled()
                                        ->content(function (Get $get) {
                                            $remaining = $get('remaining_balance') ?? 0;
                                            return 'Rs. ' . number_format((float) $remaining, 2);
                                        }),
                                ]),

                            Section::make('Make the Payment')
                                ->columns(3)
                                ->schema([
                                    Select::make('payment_type')
                                        ->label('Payment Type')
                                        ->dehydrated()
                                        ->options([
                                            'fixed' => 'Fixed Amount',
                                            'percentage' => 'Percentage',
                                        ])
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set) {
                                            $set('payment_amount', null);
                                            $set('payment_percentage', null);
                                            $set('calculated_payment', null);
                                        }),

                                    // Amount input for fixed payment
                                    TextInput::make('fix_payment_amount')
                                        ->label('Enter Amount')
                                        ->dehydrated()
                                        ->suffix('Rs.')
                                        ->live()
                                        ->required(fn ($get) => $get('payment_type') === 'fixed')
                                        ->visible(fn ($get) => $get('payment_type') === 'fixed')
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $remainingBalance = (float) ($get('remaining_balance') ?? 0);

                                            if ($state > $remainingBalance) {
                                                $set('fix_payment_amount', null);
                                                $set('calculated_payment', null);

                                                Notification::make()
                                                    ->title('Invalid Payment')
                                                    ->body('The entered amount exceeds the remaining balance.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $set('calculated_payment', $state);
                                        }),


                                    // Percentage input for percentage-based payment
                                    TextInput::make('payment_percentage')
                                        ->label('Enter Percentage')
                                        ->dehydrated()
                                        ->suffix('%')
                                        ->required(fn ($get) => $get('payment_type') === 'percentage')
                                        ->visible(fn ($get) => $get('payment_type') === 'percentage')
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $remainingBalance = (float) ($get('remaining_balance') ?? 0);
                                            $calculated = $remainingBalance * ($state / 100);

                                            if ($calculated > $remainingBalance) {
                                                $set('payment_percentage', null);
                                                $set('percent_calculated_payment', null);

                                                Notification::make()
                                                    ->title('Invalid Percentage')
                                                    ->body('Calculated payment exceeds the remaining balance.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $set('percent_calculated_payment', $calculated); 
                                        }),

                                    // Common display field for calculated amount
                                    TextInput::make('percent_calculated_payment')
                                        ->label('Calculated Payment')
                                        ->suffix('Rs.')
                                        ->disabled()
                                        ->live()
                                        ->dehydrated()
                                        ->visible(fn ($get) => $get('payment_type') === 'percentage')
                                        ->default(function ($get) {
                                            $remainingBalance = (float) ($get('remaining_balance') ?? 0);
                                            $percentage = (float) ($get('payment_percentage') ?? 0);

                                            $calculated = $remainingBalance * ($percentage / 100);

                                            return $calculated && $calculated <= $remainingBalance ? $calculated : null;
                                        }),
                                ]),
                            ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Sup ADV. Invoice ID')->sortable()->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('purchase_order_id')->label('Purchase Order ID')->sortable()->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('paid_amount')->label('Paid Amount')->sortable()
                    ->formatStateUsing(fn ($state) => 'Rs. ' . number_format((float) $state, 2)),
                TextColumn::make('remaining_amount')->label('Remaining Amount')->sortable()
                    ->formatStateUsing(fn ($state) => 'Rs. ' . number_format((float) $state, 2)),
                TextColumn::make('status')->label('Status')->sortable(),
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
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'partially_paid' => 'Partially Paid',
                        'paid' => 'Paid',
                        'deducted' => 'deducted',
                    ])
                    ->searchable(),

                Filter::make('purchase_order_id')
                    ->label('Purchase Order ID')
                    ->form([
                        TextInput::make('po_id')
                            ->label('Enter PO ID')
                            ->numeric(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['po_id'] ?? null,
                            fn ($q, $poId) => $q->where('purchase_order_id', $poId)
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('viewPdf')
                ->label('View PDF')
                ->color('success')
                ->icon('heroicon-o-eye')
                ->url(fn (SupplierAdvanceInvoice $record): string => route('supplier-advance-invoices.pdf', $record))
                ->openUrlInNewTab(),
                
                Tables\Actions\Action::make('pay')
                    ->label('Pay')
                    ->color('primary')
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn (SupplierAdvanceInvoice $record): bool =>
                        auth()->user()?->can('pay supp adv invoice') &&
                        in_array($record->status, ['pending', 'partially_paid', 'paid']) && 
                        $record->remaining_amount > 0
                    )
                    ->form([
                        Section::make('Payment Information')
                            ->columns(2)
                            ->schema([
                                Placeholder::make('current_remaining_amount')
                                    ->label('Remaining Amount')
                                    ->content(fn (SupplierAdvanceInvoice $record): string => 
                                        'Rs. ' . number_format((float) $record->remaining_amount, 2)
                                    ),
                                
                                Placeholder::make('current_paid_amount')
                                    ->label('Already Paid Amount')
                                    ->content(fn (SupplierAdvanceInvoice $record): string => 
                                        'Rs. ' . number_format((float) $record->paid_amount, 2)
                                    ),
                                
                                TextInput::make('payment_amount')
                                    ->label('Enter Payment Amount')
                                    ->required()
                                    ->numeric()
                                    ->suffix('Rs.')
                                    ->live()
                                    ->rules([
                                        fn (SupplierAdvanceInvoice $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record) {
                                            $amount = (float) $value;

                                            if ($amount <= 0) {
                                                $fail('Payment amount must be greater than zero.');
                                                return;
                                            }

                                            if ($amount > $record->remaining_amount) {
                                                $fail('Payment amount cannot exceed the invoice remaining amount of Rs. ' . number_format($record->remaining_amount, 2));
                                            }

                                            if ($record->purchase_order_id) {
                                                $poRemaining = \App\Models\PurchaseOrder::find($record->purchase_order_id)?->remaining_balance ?? 0;

                                                if ($amount > $poRemaining) {
                                                    $fail('Payment amount cannot exceed the Purchase Order remaining balance of Rs. ' . number_format($poRemaining, 2));
                                                }
                                            }
                                        },
                                    ]),

                                Select::make('payment_method')
                                    ->label('Payment Method')
                                    ->options([
                                        'cash' => 'Cash',
                                        'bank_transfer' => 'Bank Transfer',
                                        'cheque' => 'Cheque',
                                        'online' => 'Online Payment',
                                        'card' => 'Card Payment',
                                    ])
                                    ->default('cash')
                                    ->required(),
                                
                                TextInput::make('payment_reference')
                                    ->label('Payment Reference/Transaction ID')
                                    ->placeholder('Enter reference number if applicable'),
                                
                                Textarea::make('notes')
                                    ->label('Payment Notes')
                                    ->placeholder('Any additional notes about this payment')
                                    ->columnSpanFull(),
                                
                                Placeholder::make('logged_user')
                                    ->label('Payment will be recorded by')
                                    ->content(fn (): string => Auth::user()->name . ' (ID: ' . Auth::id() . ')')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->action(function (SupplierAdvanceInvoice $record, array $data) {
                        $paymentAmount = (float) $data['payment_amount'];
                        $remainingBefore = $record->remaining_amount;
                        $remainingAfter = $remainingBefore - $paymentAmount;

                        $payment = SuppAdvInvoicePayment::create([
                            'supplier_advance_invoice_id' => $record->id,
                            'payment_amount' => $paymentAmount,
                            'remaining_amount_before' => $remainingBefore,
                            'remaining_amount_after' => $remainingAfter,
                            'payment_method' => $data['payment_method'],
                            'payment_reference' => $data['payment_reference'] ?? null,
                            'notes' => $data['notes'] ?? null,
                        ]);

                        $record->update([
                            'paid_amount' => $record->paid_amount + $paymentAmount,
                            'remaining_amount' => $remainingAfter,
                            'status' => $remainingAfter <= 0 ? 'paid' : 'partially_paid',
                            'paid_date' => today(),
                            'paid_via' => $data['payment_method'],
                        ]);

                        if ($record->purchase_order_id) {
                            \App\Models\PurchaseOrder::where('id', $record->purchase_order_id)
                                ->decrement('remaining_balance', $paymentAmount);
                        }

                        //  Update provider balance
                        if ($record->provider_type === 'supplier') {
                            \App\Models\Supplier::where('supplier_id', $record->provider_id)->decrement('outstanding_balance', $paymentAmount);
                        } elseif ($record->provider_type === 'customer') {
                            \App\Models\Customer::where('customer_id', $record->provider_id)->increment('remaining_balance', $paymentAmount);
                        }
                        
                        Notification::make()
                            ->title('Payment Recorded Successfully')
                            ->body("Payment of Rs. " . number_format($paymentAmount, 2) . " has been recorded. Click below to open the receipt.")
                            ->success()
                            ->actions([
                                Action::make('viewReceipt')
                                    ->label('View Receipt PDF')
                                    ->url(route('supplier-advance.payment-receipt', [
                                        'invoice' => $record->id,
                                        'payment' => $payment->id,
                                    ]))
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                    }),


                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->status !== 'pending')
            ])
        ->defaultSort('id', 'desc') 
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
            'index' => Pages\ListSupplierAdvanceInvoices::route('/'),
            'create' => Pages\CreateSupplierAdvanceInvoice::route('/create'),
        ];
    }
}