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
                    Tab::make('Purchase Order')
                        ->schema([
                            Section::make('Purchase Order Details')->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('purchase_order_id')
                                        ->label('Purchase Order ID')
                                        ->required()
                                        ->reactive()
                                        ->rules([
                                            function () {  
                                                return function (string $attribute, $value, \Closure $fail) {
                                                    $exists = \App\Models\PurchaseOrderInvoice::where('purchase_order_id', $value)->exists();

                                                    if ($exists) {
                                                        $fail('An invoice for this Purchase Order already exists.');
                                                    }
                                                };
                                            },
                                        ])
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $numericId = ltrim($state, '0');

                                            // Fetch purchase order with supplier
                                            $purchaseOrder = \App\Models\PurchaseOrder::with('supplier')->find($numericId);

                                            if (!$purchaseOrder) {
                                                Notification::make()
                                                    ->title('Purchase Order Not Found')
                                                    ->body('The purchase order ID entered does not exist.')
                                                    ->danger()
                                                    ->send();
                                                self::clearForm($set);
                                                return;
                                            }

                                            // Set supplier and wanted date safely
                                            $set('supplier_id', $purchaseOrder->supplier_id ?? '');
                                            $set('supplier_name', optional($purchaseOrder->supplier)->name ?? '');
                                            $set('wanted_date', $purchaseOrder->wanted_date ?? '');

                                            // Fetch all register arrivals for this purchase order
                                            $registerArrivals = \App\Models\RegisterArrival::where('purchase_order_id', $numericId)->get();

                                            $allItems = collect();
                                            $hasToBeInspected = false;

                                            foreach ($registerArrivals as $arrival) {
                                                $items = \App\Models\RegisterArrivalItem::where('register_arrival_id', $arrival->id)
                                                    ->where('status', '!=', 'invoiced')
                                                    ->get();

                                                foreach ($items as $item) {
                                                    if ($item->status === 'to be inspected') {
                                                        $hasToBeInspected = true;
                                                        break 2; // Stop processing if any QC not done
                                                    }

                                                    $inventoryItem = \App\Models\InventoryItem::find($item->item_id);
                                                    $location = \App\Models\InventoryLocation::find($arrival->location_id);

                                                    $allItems->push([
                                                        'register_arrival_id' => $arrival->id,
                                                        'location_name' => optional($location)->name,
                                                        'received_date' => $arrival->received_date,
                                                        'item_id' => $item->item_id,
                                                        'item_code' => optional($inventoryItem)->item_code,
                                                        'name' => optional($inventoryItem)->name,
                                                        'quantity' => $item->quantity,
                                                        'price' => $item->price,
                                                        'status' => $item->status,
                                                        'total' => $item->total,
                                                    ]);
                                                }
                                            }

                                            if ($hasToBeInspected) {
                                                Notification::make()
                                                    ->title('QC Incomplete')
                                                    ->body('There are items where Material QC is not completed.')
                                                    ->danger()
                                                    ->duration(8000)
                                                    ->send();

                                                self::clearForm($set);
                                                return;
                                            }

                                            $set('items', $allItems->toArray());

                                            // Process Material QC
                                            $allQcRecords = collect();
                                            foreach ($registerArrivals as $arrival) {
                                                $qcRecords = \App\Models\MaterialQC::where('register_arrival_id', $arrival->id)
                                                    ->where('purchase_order_id', $numericId)
                                                    ->get();

                                                foreach ($qcRecords as $record) {
                                                    $item = \App\Models\InventoryItem::find($record->item_id);
                                                    $storeLocation = \App\Models\InventoryLocation::find($record->store_location_id);
                                                    $arrivalLocation = \App\Models\InventoryLocation::find($arrival->location_id);

                                                    $allQcRecords->push([
                                                        'register_arrival_id' => $arrival->id,
                                                        'arrival_location' => optional($arrivalLocation)->name,
                                                        'received_date' => $arrival->received_date,
                                                        'item_code' => optional($item)->item_code,
                                                        'item_name' => optional($item)->name,
                                                        'inspected_quantity' => $record->inspected_quantity,
                                                        'approved_qty' => $record->approved_qty,
                                                        'returned_qty' => $record->returned_qty,
                                                        'scrapped_qty' => $record->scrapped_qty,
                                                        'add_returned' => $record->add_returned,
                                                        'add_scrap' => $record->add_scrap,
                                                        'available_to_store' => $record->available_to_store,
                                                        'cost_of_item' => $record->cost_of_item,
                                                        'store_location' => optional($storeLocation)->name,
                                                        'total_returned' => (float) ($record->returned_qty ?? 0) + (float) ($record->add_returned ?? 0),
                                                        'total_scrapped' => (float) ($record->scrapped_qty ?? 0) + (float) ($record->add_scrap ?? 0),
                                                    ]);
                                                }
                                            }
                                            $set('material_qc_items', $allQcRecords->toArray());

                                            // Process invoice items (QC Passed + available_to_store)
                                            $invoiceItems = collect();

                                            foreach ($registerArrivals as $arrival) {
                                                $passedItems = \App\Models\RegisterArrivalItem::where('register_arrival_id', $arrival->id)
                                                    ->where('status', 'QC Passed')
                                                    ->get();

                                                foreach ($passedItems as $item) {
                                                    $invItem = \App\Models\InventoryItem::find($item->item_id);
                                                    $location = \App\Models\InventoryLocation::find($arrival->location_id);

                                                    $invoiceItems->push([
                                                        'register_arrival_id' => $arrival->id,
                                                        'item_id_i' => $item->item_id,
                                                        'item_code_i' => optional($invItem)->item_code,
                                                        'item_name_i' => optional($invItem)->name,
                                                        'stored_quantity_i' => (float) $item->quantity,
                                                        'location_id_i' => $arrival->location_id,
                                                        'location_name_i' => optional($location)->name,
                                                        'price_i' => (float) $item->price,
                                                        'total' => round((float) $item->quantity * (float) $item->price, 2),
                                                    ]);
                                                }

                                                // Material QC items with available_to_store > 0
                                                $materialQCs = \App\Models\MaterialQC::where('register_arrival_id', $arrival->id)
                                                    ->where('purchase_order_id', $numericId)
                                                    ->get();

                                                foreach ($materialQCs as $qc) {
                                                    if ($qc->available_to_store > 0) {
                                                        $invItem = \App\Models\InventoryItem::find($qc->item_id);
                                                        $location = \App\Models\InventoryLocation::find($qc->store_location_id);

                                                        $invoiceItems->push([
                                                            'register_arrival_id' => $arrival->id,
                                                            'item_id_i' => $qc->item_id,
                                                            'item_code_i' => optional($invItem)->item_code,
                                                            'item_name_i' => optional($invItem)->name,
                                                            'stored_quantity_i' => (float) $qc->available_to_store,
                                                            'location_id_i' => $qc->store_location_id,
                                                            'location_name_i' => optional($location)->name,
                                                            'price_i' => (float) $qc->cost_of_item,
                                                            'total' => round((float) $qc->available_to_store * (float) $qc->cost_of_item, 2),
                                                        ]);
                                                    }
                                                }
                                            }

                                            $set('invoice_items', $invoiceItems->toArray());

                                            // Fetch paid advance invoices
                                            $advanceInvoices = \App\Models\SupplierAdvanceInvoice::where('purchase_order_id', $numericId)
                                                ->whereIn('status', ['paid', 'partially_paid'])
                                                ->get();

                                            $set('advance_invoices', $advanceInvoices->toArray());
                                        }),

                                    TextInput::make('supplier_id')->label('SupplierID')->disabled()->dehydrated(true)->formatStateUsing(fn($state) => str_pad($state ?? 0, 5, '0', STR_PAD_LEFT)),
                                    TextInput::make('supplier_name')->label('Supplier Name')->disabled(),
                                    TextInput::make('wanted_date')->label('Wanted Date')->disabled(),
                                ]),
                                
                                Repeater::make('items')
                                    ->label('Items from All Register Arrivals')
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
