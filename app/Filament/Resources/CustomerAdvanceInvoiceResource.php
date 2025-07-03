<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerAdvanceInvoiceResource\Pages;
use App\Models\CustomerAdvanceInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Auth;

class CustomerAdvanceInvoiceResource extends Resource
{
    protected static ?string $model = CustomerAdvanceInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Invoices';

    protected static function getVariationId($orderType, $lineId, $variationName)
    {
        if ($orderType === 'customer_order') {
            $variationItem = \App\Models\VariationItem::where('customer_order_description_id', $lineId)
                ->where('variation_name', $variationName)
                ->first();
            return $variationItem ? $variationItem->id : '0';
        } elseif ($orderType === 'sample_order') {
            $sampleVariation = \App\Models\SampleOrderVariation::where('sample_order_item_id', $lineId)
                ->where('variation_name', $variationName)
                ->first();
            return $sampleVariation ? $sampleVariation->id : '0';
        }
        return '0';
    }

    protected static function loadInvoiceItems($orderType, $orderId, callable $set): void
    {
        $items = [];
        if ($orderType === 'customer_order') {
            $customerOrderItems = \App\Models\CustomerOrderDescription::with(['variationItems', 'customerOrder'])
                ->where('customer_order_id', $orderId)
                ->get();

            $items = $customerOrderItems->map(function ($item) {
                return [
                    'item_id' => $item->id,
                    'item_name' => $item->item_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
                    'variations' => $item->variationItems->map(function ($variation) {
                        return [
                            'var_item_id' => $variation->id,
                            'var_item_name' => $variation->variation_name,
                            'var_quantity' => $variation->quantity,
                            'var_price' => $variation->price,
                            'var_total' => $variation->total,
                        ];
                    })->toArray(),
                ];
            })->toArray();
        } elseif ($orderType === 'sample_order') {
            $sampleOrderItems = \App\Models\SampleOrderItem::with(['variations', 'sampleOrder'])
                ->where('sample_order_id', $orderId)
                ->get();

            $items = $sampleOrderItems->map(function ($item) {
                return [
                    'item_id' => $item->id,
                    'item_name' => $item->item_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
                    'variations' => $item->variations->map(function ($variation) {
                        return [
                            'var_item_id' => $variation->id,
                            'var_item_name' => $variation->variation_name,
                            'var_quantity' => $variation->quantity,
                            'var_price' => $variation->price,
                            'var_total' => $variation->total,
                        ];
                    })->toArray(),
                ];
            })->toArray();
        }

        $set('invoice_items', $items);
    }

    public static function form(Form $form): Form
    {
    return $form->schema([
        Tabs::make('Tabs')
            ->columnSpanFull()
            ->tabs([
                Tabs\Tab::make('Order Details')
                    ->schema([
                        Section::make('Order Details')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('order_type')
                                            ->label('Order Type')
                                            ->options([
                                                'customer_order' => 'Customer Order',
                                                'sample_order' => 'Sample Order',
                                            ])
                                            ->required()
                                            ->reactive()
                                            ->disabled(fn ($get, $record) => $record !== null)
                                            ->dehydrated()
                                            ->afterStateUpdated(function ($state, $set) {
                                                $set('order_id', null);
                                                $set('customer_id', null);
                                                $set('wanted_date', null);
                                                $set('invoice_items', []);
                                            }),

                                        Select::make('order_id')
                                            ->label('Order')
                                            ->required()
                                            ->searchable()
                                            ->disabled(fn ($get, $record) => $record !== null)
                                            ->dehydrated()
                                            ->options(function ($get) {
                                                $orderType = $get('order_type');
                                                if ($orderType === 'customer_order') {
                                                    return \App\Models\CustomerOrder::query()
                                                        ->with('customer')
                                                        ->get()
                                                        ->mapWithKeys(fn ($order) => [
                                                            $order->id => 'ID=' . $order->order_id . ' | Customer=' . ($order->customer->name ?? 'N/A') . ' | Name=' . $order->name
                                                        ]);
                                                } elseif ($orderType === 'sample_order') {
                                                    return \App\Models\SampleOrder::query()
                                                        ->with('customer')
                                                        ->get()
                                                        ->mapWithKeys(fn ($order) => [
                                                            $order->id => 'ID=' . $order->order_id . ' | Customer=' . ($order->customer->name ?? 'N/A') . ' | Name=' . $order->name
                                                        ]);
                                                }
                                                return [];
                                            })
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $set('customer_id', null);
                                                $set('wanted_date', null);

                                                $orderType = $get('order_type');
                                                if ($orderType && $state) {
                                                    if ($orderType === 'customer_order') {
                                                        $order = \App\Models\CustomerOrder::with('customer')->find($state);
                                                    } elseif ($orderType === 'sample_order') {
                                                        $order = \App\Models\SampleOrder::with('customer')->find($state);
                                                    }

                                                    if ($order) {
                                                        $set('customer_id', $order->customer_id ?? 'N/A');
                                                        $set('customer_name', $order->customer->name ?? 'N/A');
                                                        $set('wanted_date', $order->wanted_delivery_date ?? 'N/A');
                                                    } else {
                                                        $set('customer_id', 'N/A');
                                                        $set('customer_name', 'N/A');
                                                        $set('wanted_date', 'N/A');
                                                    }
                                                } else {
                                                    $set('customer_id', 'N/A');
                                                    $set('customer_name', 'N/A');
                                                    $set('wanted_date', 'N/A');
                                                }

                                                if (!$state) {
                                                    $set('invoice_items', []);
                                                    return;
                                                }

                                                $orderType = $get('order_type');
                                                static::loadInvoiceItems($orderType, $state, $set);
                                            }),

                                        TextInput::make('customer_name')
                                            ->label('Customer Name')
                                            ->disabled(),

                                        TextInput::make('wanted_date')
                                            ->label('Wanted Date')
                                            ->disabled(),
                                        ]),
                                ]),     
                        ]),

                   Tab::make('Order Items')
                    ->schema([
                        Section::make('Order Variations Data')
                            ->schema([
                                Repeater::make('invoice_items')
                                    ->label('Invoice Items')
                                    ->schema([
                                        TextInput::make('item_name')->label('Item Name')->disabled(),

                                        TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->disabled()
                                            ->visible(fn (Get $get) => empty($get('variations'))),

                                        TextInput::make('price')
                                            ->label('Price')
                                            ->disabled()
                                            ->visible(fn (Get $get) => empty($get('variations'))),

                                        TextInput::make('total')
                                            ->label('Total')
                                            ->disabled()
                                            ->visible(fn (Get $get) => empty($get('variations'))),

                                        Repeater::make('variations')
                                            ->label('Variations')
                                            ->schema([
                                                TextInput::make('var_item_name')->label('Variation Name')->disabled(),
                                                TextInput::make('var_quantity')->label('Quantity')->disabled(),
                                                TextInput::make('var_price')->label('Price')->disabled(),
                                                TextInput::make('var_total')->label('Total')->disabled(),
                                            ])
                                            ->default([])
                                            ->columns(4)
                                            ->columnSpanFull()
                                            ->disableItemCreation()
                                            ->disableItemDeletion()
                                            ->disableItemMovement(),
                                    ])
                                    ->default([])
                                    ->columns(4)
                                    ->disableItemCreation()
                                    ->disableItemDeletion()
                                    ->disableItemMovement(),

                                Placeholder::make('grand_total')
                                    ->label('Grand Total')
                                    ->content(function (Get $get) {
                                        $items = $get('invoice_items') ?? [];

                                        $grandTotal = 0;

                                        foreach ($items as $item) {
                                            // If variations exist, use their totals
                                            if (!empty($item['variations'])) {
                                                foreach ($item['variations'] as $var) {
                                                    $grandTotal += floatval($var['var_total'] ?? 0);
                                                }
                                            } else {
                                                // Use parent item total if no variations
                                                $grandTotal += floatval($item['total'] ?? 0);
                                            }
                                        }

                                        return 'Rs. ' . number_format($grandTotal, 2);
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Tab::make('Invoice Details')
                    ->schema([
                        Section::make('Grand Total Order Items')
                                ->schema([
                                    Placeholder::make('grand_total')
                                    ->label('Grand Total')
                                    ->content(function (Get $get) {
                                        $items = $get('invoice_items') ?? [];

                                        $grandTotal = 0;

                                        foreach ($items as $item) {
                                            // If variations exist, use their totals
                                            if (!empty($item['variations'])) {
                                                foreach ($item['variations'] as $var) {
                                                    $grandTotal += floatval($var['var_total'] ?? 0);
                                                }
                                            } else {
                                                // Use parent item total if no variations
                                                $grandTotal += floatval($item['total'] ?? 0);
                                            }
                                        }

                                        return 'Rs. ' . number_format($grandTotal, 2);
                                    })
                                    ->columnSpanFull(),

                                    Hidden::make('grand_total')
                                        ->dehydrated()
                                        ->default(function (Get $get) {
                                            $items = $get('invoice_items') ?? [];
                                            $grandTotal = 0;

                                            foreach ($items as $item) {
                                                if (!empty($item['variations'])) {
                                                    foreach ($item['variations'] as $var) {
                                                        $grandTotal += floatval($var['var_total'] ?? 0);
                                                    }
                                                } else {
                                                    $grandTotal += floatval($item['total'] ?? 0);
                                                }
                                            }

                                            return $grandTotal;
                                        }),
                                ]),

                            Section::make('Record the Payment')
                                ->columns(3)
                                ->schema([
                                    Select::make('payment_type')
                                        ->label('Paid Payment Type')
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
                                        ->label('Enter Received Amount')
                                        ->dehydrated()
                                        ->suffix('Rs.')
                                        ->live()
                                        ->required(fn ($get) => $get('payment_type') === 'fixed')
                                        ->visible(fn ($get) => $get('payment_type') === 'fixed')
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $items = $get('invoice_items') ?? [];

                                            $grandTotal = 0;
                                            foreach ($items as $item) {
                                                if (!empty($item['variations'])) {
                                                    foreach ($item['variations'] as $var) {
                                                        $grandTotal += floatval($var['var_total'] ?? 0);
                                                    }
                                                } else {
                                                    $grandTotal += floatval($item['total'] ?? 0);
                                                }
                                            }

                                            if ($state > $grandTotal) {
                                                $set('fix_payment_amount', null);
                                                $set('calculated_payment', null);
                                                Notification::make()
                                                    ->title('Invalid Payment')
                                                    ->body('The entered amount exceeds the Grand Total.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $set('calculated_payment', $state);
                                        }),


                                    // Percentage input for percentage-based payment
                                    TextInput::make('payment_percentage')
                                        ->label('Enter Received Percentage')
                                        ->dehydrated()
                                        ->suffix('%')
                                        ->required(fn ($get) => $get('payment_type') === 'percentage')
                                        ->visible(fn ($get) => $get('payment_type') === 'percentage')
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $items = $get('invoice_items') ?? [];

                                            $grandTotal = 0;
                                            foreach ($items as $item) {
                                                if (!empty($item['variations'])) {
                                                    foreach ($item['variations'] as $var) {
                                                        $grandTotal += floatval($var['var_total'] ?? 0);
                                                    }
                                                } else {
                                                    $grandTotal += floatval($item['total'] ?? 0);
                                                }
                                            }

                                            $calculated = $grandTotal * ($state / 100);

                                            if ($calculated > $grandTotal) {
                                                $set('payment_percentage', null);
                                                $set('percent_calculated_payment', null);
                                                Notification::make()
                                                    ->title('Invalid Percentage')
                                                    ->body('Calculated payment exceeds the Grand Total.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $set('percent_calculated_payment', $calculated);
                                        }),

                                    // Common display field for calculated amount
                                    TextInput::make('percent_calculated_payment')
                                        ->label('Calculated Paid Payment')
                                        ->suffix('Rs.')
                                        ->disabled()
                                        ->live()
                                        ->dehydrated()
                                        ->visible(fn ($get) => $get('payment_type') === 'percentage')
                                        ->default(function ($get) {
                                            $items = $get('invoice_items') ?? [];

                                            $grandTotal = 0;
                                            foreach ($items as $item) {
                                                if (!empty($item['variations'])) {
                                                    foreach ($item['variations'] as $var) {
                                                        $grandTotal += floatval($var['var_total'] ?? 0);
                                                    }
                                                } else {
                                                    $grandTotal += floatval($item['total'] ?? 0);
                                                }
                                            }

                                            $paymentPercentage = $get('payment_percentage');

                                            $calculated = $paymentPercentage ? $grandTotal * ($paymentPercentage / 100) : null;

                                            return $calculated && $calculated <= $grandTotal ? $calculated : null;
                                        }),


                                ]),

                             Section::make('Customer Advance Invoice Details')
                                ->schema([
                                    TextInput::make('cus_invoice_number')
                                        ->label('Customer Advance Invoice Number')
                                        ->required()
                                        ->dehydrated()
                                        ->numeric(),
                                    FileUpload::make('invoice_image')
                                        ->label('Upload Image')
                                        ->image()
                                        ->directory(null) 
                                        ->preserveFilenames()
                                        ->getUploadedFileNameForStorageUsing(fn ($file) => $file->getClientOriginalName())
                                        ->storeFiles(),
                                ]),
                    ]),
                ])
                ->columnspanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Cus ADV. Invoice ID')->sortable()->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('order_type')->label('Order Type')->sortable(),
                TextColumn::make('order_id')->label('Order ID')->sortable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('received_amount')->label('Received Amount')->sortable()
                    ->formatStateUsing(fn ($state) => 'Rs. ' . number_format((float) $state, 2)),
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
                //
            ])
            ->actions([
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
            'index' => Pages\ListCustomerAdvanceInvoices::route('/'),
            'create' => Pages\CreateCustomerAdvanceInvoice::route('/create'),
        ];
    }
}