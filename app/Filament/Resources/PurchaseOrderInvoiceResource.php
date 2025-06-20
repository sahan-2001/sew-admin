<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderInvoiceResource\Pages;
use App\Filament\Resources\PurchaseOrderInvoiceResource\RelationManagers;
use App\Models\PurchaseOrderInvoice;
use App\Models\PurchaseOrderInvoiceItem;
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
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;

class PurchaseOrderInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseOrderInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Invoices';
    protected static ?string $label = 'Final PO Invoice';
    protected static ?string $pluralLabel = 'Final PO Invoices';
    protected static ?string $navigationLabel = 'Final PO Invoices';

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
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $numericId = ltrim($state, '0');
                                            $purchaseOrder = \App\Models\PurchaseOrder::find($numericId);

                                            if (!$purchaseOrder) {
                                                Notification::make()
                                                    ->title('Purchase Order Not Found')
                                                    ->body('The purchase order ID entered does not exist.')
                                                    ->danger()
                                                    ->send();
                                                self::clearForm($set);
                                                return;
                                            }

                                            $set('provider_type', $purchaseOrder->provider_type);
                                            $set('provider_name', $purchaseOrder->provider_name);
                                            $set('provider_id', $purchaseOrder->provider_id);
                                            $set('wanted_date', $purchaseOrder->wanted_date);

                                            $registerArrivals = \App\Models\RegisterArrival::where('purchase_order_id', $numericId)->get();

                                            $set('register_arrival_options', $registerArrivals->mapWithKeys(function ($r) {
                                                $location = \App\Models\InventoryLocation::find($r->location_id);
                                                $locationName = $location ? $location->name : 'N/A';
                                                return [
                                                    $r->id => "ID - {$r->id} | Location - {$locationName} | Received Date - {$r->received_date}"
                                                ];
                                            })->toArray());
                                           
                                            // Fetch ALL Supplier Advance Invoices (not just latest)
                                            $advanceInvoices = \App\Models\SupplierAdvanceInvoice::where('purchase_order_id', $numericId)
                                                ->whereIn('status', ['paid', 'partially paid'])
                                                ->with(['purchaseOrder', 'supplier']) 
                                                ->get();
                                            
                                            $set('supplier_advance_invoices', $advanceInvoices->isNotEmpty()
                                                ? $advanceInvoices->map(function ($invoice) {
                                                    return [
                                                        'id' => $invoice->id,
                                                        'status' => $invoice->status,
                                                        'payment_type' => $invoice->payment_type,
                                                        'fix_payment_amount' => $invoice->fix_payment_amount,
                                                        'payment_percentage' => $invoice->payment_percentage,
                                                        'percent_calculated_payment' => $invoice->percent_calculated_payment,
                                                        'paid_amount' => $invoice->paid_amount,
                                                        'remaining_amount' => $invoice->remaining_amount,
                                                        'paid_date' => $invoice->paid_date,
                                                        'paid_via' => $invoice->paid_via,
                                                    ];
                                                })->toArray()
                                                : [[
                                                    'fix_payment_amount' => null,
                                                    'payment_percentage' => null,
                                                    'percent_calculated_payment' => null,
                                                    'paid_date' => null,
                                                    'paid_via' => null,
                                                ]]
                                            );
                                        }),

                                    Select::make('register_arrival_id')
                                        ->label('Register Arrival ID')
                                        ->options(fn (callable $get) => $get('register_arrival_options') ?? [])
                                        ->disabled(fn (callable $get) => empty($get('register_arrival_options')))
                                        ->reactive()
                                        ->required()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            // Fetch RegisterArrivalItems
                                            $items = \App\Models\RegisterArrivalItem::where('register_arrival_id', $state)->get();

                                            $set('items', $items->map(function ($item) {
                                                $inventoryItem = \App\Models\InventoryItem::find($item->item_id);
                                                return [
                                                    'id' => $item->id,
                                                    'item_id' => $item->item_id,
                                                    'item_code' => $inventoryItem?->item_code,
                                                    'name' => $inventoryItem?->name,
                                                    'quantity' => $item->quantity,
                                                    'price' => $item->price,
                                                    'status' => $item->status,
                                                    'total' => $item->total,
                                                ];
                                            })->toArray());

                                            $purchaseOrderId = ltrim($get('purchase_order_id'), '0');
                                            $qcRecords = \App\Models\MaterialQC::where('register_arrival_id', $state)
                                                ->where('purchase_order_id', $purchaseOrderId)
                                                ->get();

                                            $set('material_qc_items', $qcRecords->map(function ($record) {
                                                $item = \App\Models\InventoryItem::find($record->item_id);
                                                $storeLocation = \App\Models\InventoryLocation::find($record->store_location_id);

                                                return [
                                                    'item_code' => $item?->item_code,
                                                    'inspected_quantity' => $record->inspected_quantity,
                                                    'approved_qty' => $record->approved_qty,
                                                    'returned_qty' => $record->returned_qty,
                                                    'scrapped_qty' => $record->scrapped_qty,

                                                    'add_returned' => $record->add_returned,
                                                    'add_scrap' => $record->add_scrap,
                                                    'available_to_store' => $record->available_to_store,

                                                    'cost_of_item' => $record->cost_of_item,
                                                    'store_location' => $storeLocation?->name,

                                                    'total_returned' => (float) ($record->returned_qty ?? 0) + (float) ($record->add_returned ?? 0),
                                                    'total_scrapped' => (float) ($record->scrapped_qty ?? 0) + (float) ($record->add_scrap ?? 0),
                                                ];
                                            })->toArray());

                                            // Fetch Invoice Items
                                            $invoiceItems = collect();

                                            $passedItems = \App\Models\RegisterArrivalItem::where('register_arrival_id', $state)
                                                ->where('status', 'QC Passed')
                                                ->get();

                                            foreach ($passedItems as $item) {
                                            $invItem = \App\Models\InventoryItem::find($item->item_id);

                                            // Get the related RegisterArrival to access location_id
                                            $registerArrival = \App\Models\RegisterArrival::find($item->register_arrival_id);
                                            $location = \App\Models\InventoryLocation::find($registerArrival?->location_id);

                                            $invoiceItems->push([
                                                'item_id_i' => $item->item_id,
                                                'item_code_i' => $invItem?->item_code,
                                                'item_name_i' => $invItem?->name,
                                                'stored_quantity_i' => $item->quantity,
                                                'location_id_i' => $registerArrival?->location_id,
                                                'location_name_i' => $location?->name,
                                                'price_i' => $item->price,
                                                'total' => $item->quantity * $item->price, 
                                            ]);
                                        }

                                            $materialQCs = \App\Models\MaterialQC::where('register_arrival_id', $state)
                                                ->where('purchase_order_id', $purchaseOrderId)
                                                ->get();

                                            foreach ($materialQCs as $qc) {
                                                if ($qc->available_to_store > 0) {
                                                    $invItem = \App\Models\InventoryItem::find($qc->item_id);
                                                    $location = \App\Models\InventoryLocation::find($qc->store_location_id);

                                                    $invoiceItems->push([
                                                        'item_id_i' => $qc->item_id,
                                                        'item_code_i' => $invItem?->item_code,
                                                        'item_name_i' => $invItem?->name,
                                                        'stored_quantity_i' => $qc->available_to_store,
                                                        'location_id_i' => $qc->store_location_id,
                                                        'location_name_i' => $location?->name,
                                                        'price_i' => $qc->cost_of_item, 
                                                        'total' => $item->quantity * $item->price, 
                                                    ]);
                                                }
                                            }
                                            $set('invoice_items', $invoiceItems->toArray());

                                        }),

                                    TextInput::make('provider_type')->label('Provider Type')->disabled()->dehydrated(true),
                                    TextInput::make('provider_id')->label('Provider ID')->disabled()->dehydrated(true),
                                    TextInput::make('provider_name')->label('Provider Name')->disabled(),
                                    TextInput::make('wanted_date')->label('Wanted Date')->disabled(),
                                ]),
                                Repeater::make('items')
                                    ->label('Items in Register Arrival')
                                    ->schema([
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
                                    ->label('Grand Total of Arrived Items')
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
                            Section::make('Material QC Records')
                                ->schema([
                                    Repeater::make('material_qc_items')
                                        ->schema([
                                            TextInput::make('item_code')->label('Item Code')->disabled(),
                                            TextInput::make('inspected_quantity')->label('Inspected Qty')->disabled()->live(),
                                            TextInput::make('approved_qty')->label('Approved Qty')->disabled(),
                                            TextInput::make('returned_qty')->label('Returned Qty')->disabled(),
                                            TextInput::make('scrapped_qty')->label('Scrapped Qty')->disabled(),

                                            TextInput::make('total_returned')->label('Total Returned Qty')->disabled(),
                                            TextInput::make('total_scrapped')->label('Total Scrapped Qty')->disabled(),
                                            TextInput::make('available_to_store')->label('Total Stored Qty')->disabled(),
                                            TextInput::make('store_location')->label('Stored Location')->disabled(),
                                        ])
                                        ->columns(5)
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),
                        ]),
                        
                    Tab::make('Invoicing Item Details')
                        ->schema([
                            Section::make('Invoicing/ Stored Item Details')
                                ->schema([
                                    Repeater::make('invoice_items')
                                        ->schema([
                                            TextInput::make('item_id_i')->label('Item ID')->disabled()->dehydrated(),
                                            TextInput::make('item_code_i')->label('Item Code')->disabled(),
                                            TextInput::make('item_name_i')->label('Item Name')->disabled(),
                                            TextInput::make('stored_quantity_i')->label('Stored Quantity')->disabled()->dehydrated(),
                                            TextInput::make('location_id_i')->label('Location ID')->disabled()->dehydrated(),
                                            TextInput::make('location_name_i')->label('Location Name')->disabled(),
                                            TextInput::make('price_i')->label('Unit Price')->disabled()->dehydrated(),
                                            TextInput::make('total')
                                                ->label('Line Total')
                                                ->disabled()
                                                ->dehydrated()
                                                ->numeric()
                                                ->afterStateUpdated(function ($state, $set) {
                                                    $set('total', number_format($state, 2));
                                                })
                                                ->default(function ($get) {
                                                    $quantity = $get('stored_quantity_i') ?? 0;
                                                    $price = $get('price_i') ?? 0;
                                                    return $quantity * $price;
                                                })
                                                ->formatStateUsing(function ($state) {
                                                    return number_format($state, 2);
                                                })
                                        ])
                                        ->columns(4)
                                        ->disabled()
                                        ->dehydrated(true),

                                    Placeholder::make('grand_total')
                                    ->label('Grand Total of Invoicing Items')
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
                                    Repeater::make('supplier_advance_invoices')
                                        ->label('')
                                        ->schema([
                                            Hidden::make('id'),
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
                                        ->disableItemCreation()
                                        ->dehydrated(true),

                                    Placeholder::make('total_paid_amount')
                                        ->label('Total Paid Amount')
                                        ->content(fn (Get $get): string => 
                                            'Rs. ' . number_format(
                                                collect($get('supplier_advance_invoices') ?? [])
                                                    ->sum(fn ($item) => floatval($item['paid_amount'] ?? 0)),
                                                2
                                            )
                                        ),

                                    Placeholder::make('total_remaining_amount')
                                        ->label('Total Remaining Amount')
                                        ->content(fn (Get $get): string => 
                                            'Rs. ' . number_format(
                                                collect($get('supplier_advance_invoices') ?? [])
                                                    ->sum(fn ($item) => floatval($item['remaining_amount'] ?? 0)),
                                                2
                                            )
                                        ),
                                ])
                        ]),
                    Tab::make('Payment Details')
                        ->schema([
                            Section::make('Summary')
                                ->columns(2)
                                ->schema([
                                    Placeholder::make('grand_total')
                                    ->label('Grand Total of Invoicing Items')
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
                                                collect($get('supplier_advance_invoices') ?? [])
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
                                    Placeholder::make('payment_due')
                                        ->label('Payment Due')
                                        ->content(fn (Get $get): string =>
                                            'Rs. ' . number_format(
                                                // Sum of item totals
                                                (collect($get('items') ?? [])->sum(fn ($item) => floatval($item['total'] ?? 0)))
                                                +
                                                (collect($get('additional_costs') ?? [])->sum(fn ($item) => floatval($item['total_c'] ?? 0)))
                                                -
                                                (collect($get('supplier_advance_invoices') ?? [])->sum(fn ($item) => floatval($item['paid_amount'] ?? 0)))
                                                -
                                                (collect($get('discounts / deductions') ?? [])->sum(fn ($item) => floatval($item['total_d'] ?? 0))),
                                                2
                                            )
                                        ),
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
        $set('location_id', null);
        $set('location_name', null);
        $set('received_date', null);
        $set('invoice_number', null);
        $set('register_arrival_id', null);
        $set('register_arrival_options', []);
        $set('material_qc_items', []);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ...(
                Auth::user()->can('view audit columns')
                    ? [
                        TextColumn::make('created_by')->label('Created By')->toggleable()->sortable(),
                        TextColumn::make('updated_by')->label('Updated By')->toggleable()->sortable(),
                        TextColumn::make('created_at')->label('Created At')->toggleable()->dateTime()->sortable(),
                        TextColumn::make('updated_at')->label('Updated At')->toggleable()->dateTime()->sortable(),
                    ]
                    : []
                    ),
            ])
            ->filters([
                //
            ])
            ->actions([
            ]);
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
