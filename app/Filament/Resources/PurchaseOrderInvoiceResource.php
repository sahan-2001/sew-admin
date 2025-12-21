<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderInvoiceResource\Pages;
use App\Filament\Resources\PurchaseOrderInvoiceResource\RelationManagers;
use App\Models\PurchaseOrderInvoice;
use App\Models\PurchaseOrderInvoiceItem;
use App\Models\PoInvoicePayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextArea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action; 
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;


class PurchaseOrderInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseOrderInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'PO Invoices';
    protected static ?string $label = 'Final PO Invoice';
    protected static ?string $pluralLabel = 'Final PO Invoices';
    protected static ?string $navigationLabel = 'Final PO Invoices';
    protected static ?int $navigationSort = 8;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('view purchase order invoices') ?? false;
    }
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Form Tabs')
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
                                        ->afterStateUpdated(function ($state, $set, $get) {
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

                                                // Fetch supplier control account with related chart accounts
                                                $supplierId = $supplier?->supplier_id;
                                                if ($supplierId) {
                                                    $supplierControlAccount = \App\Models\SupplierControlAccount::with([
                                                        'purchaseAccount',
                                                        'purchaseDiscountAccount'
                                                    ])->where('supplier_id', $supplierId)->first();

                                                    if ($supplierControlAccount) {
                                                        // Set the control account ID
                                                        $set('supplier_control_account_id', $supplierControlAccount->id);
                                                        
                                                        // Set purchase account details
                                                        if ($supplierControlAccount->purchaseAccount) {
                                                            $purchaseAccount = $supplierControlAccount->purchaseAccount;
                                                            $set('purchase_account_id', $purchaseAccount->id);
                                                            $set('purchase_account_display', 
                                                                'ID: ' . str_pad($purchaseAccount->id, 5, '0', STR_PAD_LEFT) . 
                                                                ' | Name: ' . $purchaseAccount->account_name .
                                                                ' | Balance: Rs. ' . number_format($purchaseAccount->balance ?? 0, 2)
                                                            );
                                                        } else {
                                                            $set('purchase_account_id', null);
                                                            $set('purchase_account_display', 'No purchase account configured');
                                                        }

                                                        // Set purchase discount account details
                                                        if ($supplierControlAccount->purchaseDiscountAccount) {
                                                            $purchaseDiscountAccount = $supplierControlAccount->purchaseDiscountAccount;
                                                            $set('purchase_discount_account_id', $purchaseDiscountAccount->id);
                                                            $set('purchase_discount_account_display', 
                                                                'ID: ' . str_pad($purchaseDiscountAccount->id, 5, '0', STR_PAD_LEFT) . 
                                                                ' | Name: ' . $purchaseDiscountAccount->account_name .
                                                                ' | Balance: Rs. ' . number_format($purchaseDiscountAccount->balance ?? 0, 2)
                                                            );
                                                        } else {
                                                            $set('purchase_discount_account_id', null);
                                                            $set('purchase_discount_account_display', 'No purchase discount account configured');
                                                        }
                                                    } else {
                                                        $set('supplier_control_account_id', null);
                                                        $set('purchase_account_id', null);
                                                        $set('purchase_account_display', 'No control account found');
                                                        $set('purchase_discount_account_id', null);
                                                        $set('purchase_discount_account_display', 'No control account found');
                                                    }
                                                }
                                            } else {
                                                // Clear all fields if PO not found
                                                $set('supplier_id', null);
                                                $set('supplier_name', 'Unknown');
                                                $set('supplier_phone', null);
                                                $set('supplier_email', null);
                                                $set('wanted_date', null);
                                                $set('remaining_balance', 0);
                                                $set('status', null);
                                                $set('purchase_order_items', []);
                                                $set('supplier_control_account_id', null);
                                                $set('purchase_account_id', null);
                                                $set('purchase_account_display', 'Select a PO to see accounts');
                                                $set('purchase_discount_account_id', null);
                                                $set('purchase_discount_account_display', 'Select a PO to see accounts');
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

                            Section::make('Ledger Account Configuration')
                                ->columns(2)
                                ->schema([
                                    // Hidden fields for account IDs
                                    Hidden::make('supplier_control_account_id')
                                        ->dehydrated(),
                                        
                                    Hidden::make('purchase_account_id')
                                        ->dehydrated(),
                                        
                                    Hidden::make('purchase_discount_account_id')
                                        ->dehydrated(),

                                    // Purchase Account Section
                                    TextInput::make('purchase_account_display')
                                        ->label('Purchase Account')
                                        ->disabled()
                                        ->columns(1)
                                        ->extraAttributes(['class' => 'font-bold text-blue-600']),

                                    // Purchase Discount Account Section
                                    TextInput::make('purchase_discount_account_display')
                                        ->label('Purchase Discount Account')
                                        ->disabled()
                                        ->columns(1)
                                        ->extraAttributes(['class' => 'font-bold text-green-600']),
                                    ]),


                        Section::make('Received Items from Register Arrivals')
                            ->columns(2)
                            ->schema([ 
                                Repeater::make('items')
                                    ->schema([
                                        TextInput::make('register_arrival_id')->label('Register Arrival ID')->disabled(),
                                        TextInput::make('location_name')->label('Location')->disabled(),
                                        TextInput::make('received_date')->label('Received Date')->disabled(),
                                        TextInput::make('item_id')->label('Item ID')->disabled()->hidden(),
                                        TextInput::make('item_code')->label('Item Code')->disabled(),
                                        TextInput::make('name')->label('Item Name')->disabled(),
                                        TextInput::make('quantity')->label('Received Quantity')->disabled(),
                                        TextInput::make('price')->label('Unit Price')->disabled(),
                                        TextInput::make('status')->label('QC Status')->disabled(),
                                        TextInput::make('total')->label('Line Total')->disabled(),
                                    ])
                                    ->columnSpan('full')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columns(6),

                                Placeholder::make('grand_total_of_arrivals')
                                    ->label('Grand Total of All Arrived Items')
                                    ->content(fn (Get $get): string =>
                                        'Rs. ' . number_format(
                                            collect($get('items') ?? [])
                                                ->sum(fn ($item) => floatval($item['total'] ?? 0)),
                                            2
                                        )
                                    ),
                            ]),
                        ]),

                    Tab::make('Material QC')
                        ->schema([
                            Section::make('Material QC Records from All Register Arrivals')
                                ->schema([
                                    Repeater::make('material_qc_items')
                                        ->schema([
                                            TextInput::make('register_arrival_id')->label('Register Arrival ID')->disabled(),
                                            TextInput::make('arrival_location')->label('Arrival Location')->disabled(),
                                            TextInput::make('received_date')->label('Received Date')->disabled(),
                                            TextInput::make('item_code')->label('Item Code')->disabled(),
                                            TextInput::make('item_name')->label('Item Name')->disabled(),
                                            TextInput::make('inspected_quantity')->label('Inspected Qty')->disabled(),
                                            TextInput::make('approved_qty')->label('Approved Qty')->disabled(),
                                            TextInput::make('returned_qty')->label('Returned Qty')->disabled(),
                                            TextInput::make('scrapped_qty')->label('Scrapped Qty')->disabled(),
                                            TextInput::make('total_returned')->label('Total Returned Qty')->disabled(),
                                            TextInput::make('total_scrapped')->label('Total Scrapped Qty')->disabled(),
                                            TextInput::make('available_to_store')->label('Total Stored Qty')->disabled(),
                                            TextInput::make('store_location')->label('Stored Location')->disabled(),
                                        ])
                                        ->columns(6)
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),
                        ]),
                        
                    Tab::make('Invoiced / Stored Item Details')
                        ->schema([
                            Section::make('Invoiced / Stored Item Details from All Register Arrivals')
                                ->schema([
                                    Repeater::make('invoice_items')
                                        ->schema([
                                            TextInput::make('register_arrival_id')->label('Register Arrival ID')->disabled()->dehydrated(),
                                            TextInput::make('item_id_i')->label('Item ID')->disabled()->dehydrated(),
                                            TextInput::make('item_code_i')->label('Item Code')->disabled(),
                                            TextInput::make('item_name_i')->label('Item Name')->disabled(),
                                            TextInput::make('stored_quantity_i')->label('Stored Quantity')->disabled()->dehydrated()->reactive(),
                                            TextInput::make('location_id_i')->label('Location ID')->disabled()->dehydrated(),
                                            TextInput::make('location_name_i')->label('Location Name')->disabled(),
                                            TextInput::make('price_i')->label('Unit Price')->disabled()->dehydrated()->reactive(),
                                            TextInput::make('total')
                                                ->label('Line Total')
                                                ->reactive()
                                                ->dehydrated()
                                                ->numeric(),
                                        ])
                                        ->columns(5)
                                        ->disabled()
                                        ->dehydrated(true),

                                    Placeholder::make('grand_total')
                                    ->label('Grand Total of All Invoiced / Stored Items')
                                    ->content(fn (Get $get): string =>
                                        'Rs. ' . number_format(
                                            collect($get('invoice_items') ?? [])
                                                ->sum(fn ($item) => floatval($item['total'] ?? 0)),
                                            2
                                        )
                                    ),
                                ]),
                        ]),

                    Tab::make('Paid Advance Invoices')
                        ->schema([
                            Section::make('Supplier Advance Invoices')
                                ->schema([
                                    Repeater::make('advance_invoices')
                                        ->label('')
                                        ->schema([
                                            Hidden::make('id')->label('ID')->dehydrated(true),

                                            TextInput::make('payment_type')
                                                ->label('Payment Type')
                                                ->disabled(),
                                            TextInput::make('fix_payment_amount')
                                                ->label('Fixed Amount')
                                                ->disabled()
                                                ->numeric()
                                                ->visible(fn (Get $get): bool => $get('payment_type') === 'fixed'),
                                            TextInput::make('payment_percentage')
                                                ->label('Percentage')
                                                ->disabled()
                                                ->numeric()
                                                ->visible(fn (Get $get): bool => $get('payment_type') === 'percentage'),
                                            TextInput::make('percent_calculated_payment')
                                                ->label('Calculated Amount')
                                                ->disabled()
                                                ->visible(fn (Get $get): bool => $get('payment_type') === 'percentage'),
                                            TextInput::make('paid_amount')
                                                ->label('Paid Amount')
                                                ->disabled()
                                                ->numeric()
                                                ->dehydrated(),
                                            TextInput::make('remaining_amount')
                                                ->label('Remaining Amount')
                                                ->disabled()
                                                ->numeric(),
                                            DatePicker::make('paid_date')
                                                ->label('Paid Date')
                                                ->disabled(),
                                            TextInput::make('paid_via')
                                                ->label('Paid Via')
                                                ->disabled(),
                                        ])
                                        ->columns(5)
                                        ->columnSpanFull()
                                        ->minItems(0)
                                        ->disableItemCreation()
                                        ->disableItemDeletion()
                                        ->dehydrated(true),

                                    Placeholder::make('total_paid_amount')
                                        ->label('Total Paid Amount')
                                        ->content(fn (Get $get): string => 
                                            'Rs. ' . number_format(
                                                collect($get('advance_invoices') ?? [])
                                                    ->sum(fn ($item) => floatval($item['paid_amount'] ?? 0)),
                                                2
                                            )
                                        ),

                                    Placeholder::make('total_remaining_amount')
                                        ->label('Total Remaining Amount')
                                        ->content(fn (Get $get): string => 
                                            'Rs. ' . number_format(
                                                collect($get('advance_invoices') ?? [])
                                                    ->sum(fn ($item) => floatval($item['remaining_amount'] ?? 0)),
                                                2
                                            )
                                        ),
                                ])
                        ]),

                    Tab::make('Payment Details')
                        ->schema([
                            Section::make('Summary')
                                ->columns(3)
                                ->schema([
                                    Placeholder::make('grand_total_of_arrivals')
                                        ->label('Grand Total of All Arrived Items')
                                        ->content(fn (Get $get): string =>
                                            'Rs. ' . number_format(
                                                collect($get('items') ?? [])
                                                    ->sum(fn ($item) => floatval($item['total'] ?? 0)),
                                                2
                                            )
                                        ),
                                    
                                    Placeholder::make('grand_total')
                                        ->label('Grand Total of Invoiced/Stored Items')
                                        ->content(fn (Get $get): string =>
                                            'Rs. ' . number_format(
                                                collect($get('invoice_items') ?? [])
                                                    ->sum(fn ($item) => floatval($item['total'] ?? 0)),
                                                2
                                            )
                                        ),
                                    
                                    Placeholder::make('total_paid_amount')
                                        ->label('Total Paid Amount')
                                        ->content(fn (Get $get): string => 
                                            'Rs. ' . number_format(
                                                collect($get('advance_invoices') ?? [])
                                                    ->sum(fn ($item) => floatval($item['paid_amount'] ?? 0)),
                                                2
                                            )
                                        ),
                                    ]),

                            Section::make('Additional Costs')
                                ->columns(1)
                                ->schema([
                                    Repeater::make('additional_costs')
                                        ->label('Additional Cost Items')
                                        ->schema([
                                            TextInput::make('description_c')
                                                ->label('Description')
                                                ->required()
                                                ->columnSpan(1),

                                            TextInput::make('unit_rate_c')
                                                ->label('Unit Rate')
                                                ->numeric()
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $qty = (float) $get('quantity_c');
                                                    $set('total_c', $qty * $state);
                                                })
                                                ->columnSpan(1),

                                            TextInput::make('quantity_c')
                                                ->label('Quantity')
                                                ->numeric()
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $rate = (float) $get('unit_rate_c');
                                                    $set('total_c', $rate * $state);
                                                })
                                                ->columnSpan(1),

                                            TextInput::make('uom_c')
                                                ->label('UOM')
                                                ->required()
                                                ->columnSpan(1),

                                            TextInput::make('total_c')
                                                ->label('Total')
                                                ->disabled()
                                                ->dehydrated()
                                                ->numeric()
                                                ->columnSpan(1),

                                            DatePicker::make('date_c')
                                                ->label('Date')
                                                ->required()
                                                ->columnSpan(1),

                                            TextInput::make('remarks_c')
                                                ->label('Remarks')
                                                ->nullable()
                                                ->columnSpanFull(), 
                                        ])
                                        ->columns(6)
                                        ->createItemButtonLabel('Add Cost Item')
                                        ->defaultItems(0)
                                        ->minItems(0)
                                        ->dehydrated(),

                                    Placeholder::make('total_additional_cost')
                                        ->label('Total Additional Cost')
                                        ->content(fn (Get $get): string => 
                                            'Rs. ' . number_format(
                                                collect($get('additional_costs') ?? [])
                                                    ->sum(fn ($item) => floatval($item['total_c'] ?? 0)),
                                                2
                                            )
                                        ),
                                
                                ]),

                            Section::make('Discounts / Deductions')
                                ->columns(1)
                                ->schema([
                                    Repeater::make('discounts_deductions')
                                        ->label('Discounts / Deductions')
                                        ->schema([
                                            TextInput::make('description_d')
                                                ->label('Description')
                                                ->required()
                                                ->columnSpan(1),

                                            TextInput::make('unit_rate_d')
                                                ->label('Unit Rate')
                                                ->numeric()
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $qty = (float) $get('quantity_d');
                                                    $set('total_d', $qty * $state);
                                                })
                                                ->columnSpan(1),

                                            TextInput::make('quantity_d')
                                                ->label('Quantity')
                                                ->numeric()
                                                ->required()
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $rate = (float) $get('unit_rate_d');
                                                    $set('total_d', $rate * $state);
                                                })
                                                ->columnSpan(1),

                                            TextInput::make('uom_d')
                                                ->label('UOM')
                                                ->required()
                                                ->columnSpan(1),

                                            TextInput::make('total_d')
                                                ->label('Total')
                                                ->disabled()
                                                ->dehydrated()
                                                ->numeric()
                                                ->columnSpan(1),

                                            DatePicker::make('date_d')
                                                ->label('Date')
                                                ->required()
                                                ->columnSpan(1),

                                            TextInput::make('remarks_d')
                                                ->label('Remarks')
                                                ->nullable()
                                                ->columnSpanFull(), 
                                        ])
                                        ->columns(6)
                                        ->createItemButtonLabel('Add Discount/Deduction Item')
                                        ->defaultItems(0)
                                        ->minItems(0)
                                        ->dehydrated(),

                                    Placeholder::make('total_discounts_deductions')
                                        ->label('Total Discounts / Deductions')
                                        ->content(fn (Get $get): string => 
                                            'Rs. ' . number_format(
                                                collect($get('discounts_deductions') ?? [])
                                                    ->sum(fn ($item) => floatval($item['total_d'] ?? 0)),
                                                2
                                            )
                                        ),
                                ]),
                                    
                            Section::make('Payment Details')
                                ->columns(2)
                                ->schema([
                                    Select::make('total_calculation_method')
                                        ->label('Total Calculation Method')
                                        ->options([
                                            'invoice_items' => 'Invoiced / Stored Items Total',
                                            'material_qc' => 'Arrived Items Total',
                                        ])
                                        ->default('invoice_items')
                                        ->required()
                                        ->live(),
                                        
                                    Placeholder::make('payment_due')
                                        ->label('Payment Due')
                                        ->content(function (Get $get): string {
                                            // Determine which total to use based on selection
                                            $itemsTotal = $get('total_calculation_method') === 'material_qc'
                                                ? collect($get('items') ?? [])->sum(fn ($item) => floatval($item['total'] ?? 0))
                                                : collect($get('invoice_items') ?? [])->sum(fn ($item) => floatval($item['total'] ?? 0));
                                            
                                            return 'Rs. ' . number_format(
                                                $itemsTotal
                                                + (collect($get('additional_costs') ?? [])->sum(fn ($item) => floatval($item['total_c'] ?? 0)))
                                                - (collect($get('advance_invoices') ?? [])->sum(fn ($item) => floatval($item['paid_amount'] ?? 0)))
                                                - (collect($get('discounts_deductions') ?? [])->sum(fn ($item) => floatval($item['total_d'] ?? 0))),
                                                2
                                            );
                                        }),
                                ])
                        ]),

                ])
                ->columnspanFull(),
        ]);
    }

    protected static function clearForm(callable $set): void
    {
        $set('items', []);
        $set('provider_type', null);
        $set('provider_name', null);
        $set('provider_id', null);
        $set('wanted_date', null);
        $set('invoice_number', null);
        $set('material_qc_items', []);
        $set('invoice_items', []);
        $set('advance_invoices', []);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Invoice ID')->sortable()->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('purchase_order_id')->label('PO ID')->sortable()->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('status')->label('Status')->sortable(),
                TextColumn::make('adv_paid')->label('Advance Paid')->sortable()->toggleable(),
                TextColumn::make('due_payment')->label('Full Payment Due')->sortable(),
                TextColumn::make('due_payment_for_now')->label('Payment Due (now)')->sortable(),
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
                    ->label('Invoice Status')
                    ->options([
                        'pending' => 'Pending',
                        'partially_paid' => 'Partially Paid',
                        'paid' => 'Paid',
                    ])
                    ->searchable(),

                Filter::make('purchase_order_id')
                    ->label('PO ID')
                    ->form([
                        TextInput::make('po_id')->label('Enter PO ID')->numeric(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when($data['po_id'], fn ($q, $poId) =>
                            $q->where('purchase_order_id', $poId)
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('pdf')
                    ->label('View PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->url(fn (PurchaseOrderInvoice $record): string => route('purchase-order-invoice.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('pay')
                    ->label('Pay Due Amount')
                    ->color('primary')
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn (PurchaseOrderInvoice $record): bool =>
                        auth()->user()?->can('pay purchase order invoice') &&
                        in_array($record->status, ['pending', 'partially_paid']) &&
                        $record->due_payment_for_now > 0
                    )
                    ->form([
                        Section::make('Payment Information')
                            ->columns(2)
                            ->schema([
                                Placeholder::make('current_due_payment')
                                    ->label('Due Payment Amount')
                                    ->content(fn (PurchaseOrderInvoice $record): string =>
                                        'Rs. ' . number_format((float) $record->due_payment_for_now, 2)
                                    ),

                                Placeholder::make('already_advanced_paid')
                                    ->label('Already Advanced Paid')
                                    ->content(fn (PurchaseOrderInvoice $record): string =>
                                        'Rs. ' . number_format((float) $record->adv_paid, 2)
                                    ),

                                TextInput::make('payment_amount')
                                    ->label('Enter Payment Amount')
                                    ->required()
                                    ->numeric()
                                    ->suffix('Rs.')
                                    ->live()
                                    ->rules([
                                        fn (PurchaseOrderInvoice $record) => function (string $attribute, $value, \Closure $fail) use ($record) {
                                            $amount = (float) $value;
                                            if ($amount <= 0) {
                                                $fail('Payment amount must be greater than zero.');
                                                return;
                                            }
                                            if ($amount > (float) $record->due_payment_for_now) {
                                                $fail('Payment amount cannot exceed the due payment amount of Rs. ' . number_format($record->due_payment_for_now, 2));
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
                                    ->label('Payment recorded by')
                                    ->content(fn () => Auth::user()->name . ' (ID: ' . Auth::id() . ')')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->action(function (PurchaseOrderInvoice $record, array $data) {
                    $paymentAmount = (float) $data['payment_amount'];
                    $remainingBefore = $record->due_payment_for_now;
                    $remainingAfter = $remainingBefore - $paymentAmount;

                    // Create payment record
                    $payment = PoInvoicePayment::create([
                        'purchase_order_invoice_id' => $record->id,
                        'payment_amount' => $paymentAmount,
                        'remaining_amount_before' => $remainingBefore,
                        'remaining_amount_after' => $remainingAfter,
                        'payment_method' => $data['payment_method'],
                        'payment_reference' => $data['payment_reference'] ?? null,
                        'notes' => $data['notes'] ?? null,
                    ]);

                    // Update invoice
                    $record->update([
                        'due_payment_for_now' => $remainingAfter,
                        'status' => $remainingAfter <= 0 ? 'paid' : 'partially_paid',
                        'paid_date' => now(),
                        'paid_via' => $data['payment_method'],
                    ]);

                    //  Update provider balance
                    if ($record->provider_type === 'supplier') {
                        \App\Models\Supplier::where('supplier_id', $record->provider_id)->decrement('outstanding_balance', $paymentAmount);
                    } elseif ($record->provider_type === 'customer') {
                        \App\Models\Customer::where('customer_id', $record->provider_id)->increment('remaining_balance', $paymentAmount);
                    }

                    // Update purchase order's remaining balance if it exists
                    if ($record->purchase_order_id) {
                        $purchaseOrder = \App\Models\PurchaseOrder::find($record->purchase_order_id);
                        if ($purchaseOrder) {
                            $purchaseOrder->decrement('remaining_balance', $paymentAmount);
                            
                            // Update status to closed if fully paid
                            if ($remainingAfter <= 0) {
                                $purchaseOrder->update(['status' => 'closed']);
                            }
                        }
                    }

                    Notification::make()
                        ->title('Payment Recorded Successfully')
                        ->body("Payment of Rs. " . number_format($paymentAmount, 2) . " has been recorded. Click below to open the receipt.")
                        ->success()
                        ->actions([
                            Action::make('viewReceipt')
                                ->label('View Receipt PDF')
                                ->url(route('purchase-order-invoice.payment-receipt', [
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
            'index' => Pages\ListPurchaseOrderInvoices::route('/'),
            'create' => Pages\CreatePurchaseOrderInvoice::route('/create'),
        ];
    }
}
