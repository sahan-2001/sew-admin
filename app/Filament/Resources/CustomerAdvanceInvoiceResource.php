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
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderDescription;
use App\Models\VariationItem;
use App\Models\SampleOrder;
use App\Models\SampleOrderItem;
use App\Models\SampleOrderVariation;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\Action;


class CustomerAdvanceInvoiceResource extends Resource
{
    protected static ?string $model = CustomerAdvanceInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Invoices';
    protected static ?string $label = 'Customer Invoice';
    protected static ?string $pluralLabel = 'Customer Invoices';
    protected static ?string $navigationLabel = 'Customer Invoices';


    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Order Details')
                        ->schema([
                            Section::make('Order Details')
                                ->columns(2)
                                ->schema([
                                    Select::make('order_type')
                                        ->label('Order Type')
                                        ->options([
                                            'customer' => 'Customer Order',
                                            'sample' => 'Sample Order',
                                        ])
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Set $set) {
                                            $set('order_id', null);
                                            $set('customer_id', null);
                                            $set('wanted_delivery_date', null);
                                            $set('special_notes', null);
                                            $set('status', null);
                                            $set('grand_total', null);
                                            $set('remaining_balance', null);
                                            $set('items', []);
                                        }),

                                    Select::make('order_id')
                                        ->label('Order')
                                        ->searchable()
                                        ->options(function (Get $get) {
                                            $type = $get('order_type');
                                            if ($type === 'customer') {
                                                return \App\Models\CustomerOrder::all()
                                                    ->mapWithKeys(fn ($order) => [
                                                        $order->order_id => "ID={$order->order_id} | Name={$order->name} | wanted date={$order->wanted_delivery_date}"
                                                    ]);
                                            } elseif ($type === 'sample') {
                                                return \App\Models\SampleOrder::all()
                                                    ->mapWithKeys(fn ($order) => [
                                                        $order->order_id => "ID={$order->order_id} | Name={$order->name} | wanted date={$order->wanted_delivery_date}"
                                                    ]);
                                            }
                                            return [];
                                        })
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $type = $get('order_type');
                                            $orderId = $state;

                                            if (!$orderId) {
                                                $set('customer_id', null);
                                                $set('wanted_delivery_date', null);
                                                $set('special_notes', null);
                                                $set('status', null);
                                                $set('grand_total', null);
                                                $set('remaining_balance', null);
                                                $set('items', []);
                                                return;
                                            }

                                            if ($type === 'customer') {
                                                $order = \App\Models\CustomerOrder::with(['orderItems.variationItems'])
                                                    ->where('order_id', $orderId)
                                                    ->first();
                                            } elseif ($type === 'sample') {
                                                $order = \App\Models\SampleOrder::with(['items.variations'])
                                                    ->where('order_id', $orderId)
                                                    ->first();
                                            } else {
                                                $order = null;
                                            }

                                            if ($order) {
                                                if (floatval($order->remaining_balance) === 0.0) {
                                                    $set('order_id', null);
                                                    $set('customer_id', null);
                                                    $set('wanted_delivery_date', null);
                                                    $set('special_notes', null);
                                                    $set('status', null);
                                                    $set('grand_total', null);
                                                    $set('remaining_balance', null);
                                                    $set('items', []);

                                                    Notification::make()
                                                        ->title('This order has already been fully paid.')
                                                        ->body('Please select a different order.')
                                                        ->danger()
                                                        ->send();

                                                    return;
                                                }

                                                $set('customer_id', $order->customer_id ?? null);
                                                $set('wanted_delivery_date', $order->wanted_delivery_date ?? null);
                                                $set('special_notes', $order->special_notes ?? null);
                                                $set('status', $order->status ?? null);
                                                $set('grand_total', $order->grand_total ?? null);
                                                $set('remaining_balance', $order->remaining_balance ?? null);

                                                $items = [];
                                                $orderItems = $type === 'customer' ? $order->orderItems : $order->items;

                                                if (!is_iterable($orderItems)) {
                                                    $orderItems = [];
                                                }

                                                foreach ($orderItems as $item) {
                                                    $hasVariations = $item->is_variation ?? false;
                                                    $variations = $type === 'customer' ? ($item->variationItems ?? []) : ($item->variations ?? []);

                                                    if ($hasVariations && is_iterable($variations) && count($variations) > 0) {
                                                        foreach ($variations as $variation) {
                                                            $items[] = [
                                                                'item_name' => $item->item_name,
                                                                'variation_name' => $variation->variation_name,
                                                                'quantity' => $variation->quantity,
                                                                'price' => $variation->price,
                                                                'total' => $variation->total,
                                                                'note' => $item->note,
                                                            ];
                                                        }
                                                    } else {
                                                        $items[] = [
                                                            'item_name' => $item->item_name,
                                                            'variation_name' => $item->variation_name,
                                                            'quantity' => $item->quantity,
                                                            'price' => $item->price,
                                                            'total' => $item->total,
                                                            'note' => $item->note,
                                                        ];
                                                    }
                                                }

                                                $set('items', $items);
                                            } else {
                                                $set('customer_id', null);
                                                $set('wanted_delivery_date', null);
                                                $set('special_notes', null);
                                                $set('status', null);
                                                $set('grand_total', null);
                                                $set('remaining_balance', null);
                                                $set('items', []);
                                            }
                                        }),

                                    TextInput::make('customer_id')
                                        ->label('Customer ID')
                                        ->disabled(),

                                    DatePicker::make('wanted_delivery_date')
                                        ->label('Wanted Delivery Date')
                                        ->disabled(),

                                    TextInput::make('status')
                                        ->label('Order Status')
                                        ->disabled(),

                                    TextInput::make('grand_total')
                                        ->label('Grand Total')
                                        ->numeric()
                                        ->disabled(),
                                ]),
                        ]),

                    Tabs\Tab::make('Order Items')
                        ->schema([
                            Section::make('Order Items')
                                ->schema([
                                    Repeater::make('items')
                                        ->columns(6)  
                                        ->schema([
                                            Grid::make(6)
                                                ->schema([
                                                    TextInput::make('item_name')
                                                        ->label('Item Name')
                                                        ->columnSpan(2)
                                                        ->disabled(),

                                                    TextInput::make('variation_name')
                                                        ->label('Variation')
                                                        ->columnSpan(1)
                                                        ->disabled(),

                                                    TextInput::make('quantity')
                                                        ->label('Qty')
                                                        ->numeric()
                                                        ->columnSpan(1)
                                                        ->disabled(),

                                                    TextInput::make('price')
                                                        ->label('Price')
                                                        ->numeric()
                                                        ->columnSpan(1)
                                                        ->disabled(),

                                                    TextInput::make('total')
                                                        ->label('Total')
                                                        ->numeric()
                                                        ->columnSpan(1)
                                                        ->disabled(),
                                                ]),
                                        ])
                                        ->itemLabel(fn (array $state): ?string => 
                                            $state['item_name'] . 
                                            ($state['variation_name'] ? ' (' . $state['variation_name'] . ')' : '')
                                        )
                                        ->disabled()
                                        ->collapsible()
                                        ->collapsed(),
                                ]),
                        ]),

                Tab::make('Invoice Details')
                    ->schema([
                        Section::make('Grand Total Order Items')
                                ->columns(3)
                                ->schema([
                                    TextInput::make('grand_total')
                                        ->label('Item Grand Total')
                                        ->numeric()
                                        ->readonly()
                                        ->dehydrated()
                                        ->prefix('Rs.')
                                        ->placeholder(fn (Get $get) => $get('grand_total') ?? ''),

                                    TextInput::make('remaining_balance')
                                        ->label('Remaining Balance of Order')
                                        ->numeric()
                                        ->readonly()
                                        ->dehydrated()
                                        ->prefix('Rs.'),

                                    TextInput::make('customer_id')
                                        ->label('Customer ID')
                                        ->readonly()
                                        ->dehydrated()
                                ]),

                            Section::make('Record the Payment')
                                ->columns(3)
                                ->schema([
                                    TextInput::make('amount')
                                        ->label('Payment Amount (Rs)')
                                        ->numeric()
                                        ->required()
                                        ->live(debounce: 300)
                                        ->afterStateUpdated(function ($state, $set, Get $get) {
                                            $grandTotal = (float)($get('grand_total') ?? 1);
                                            $remainingBalance = (float)($get('remaining_balance') ?? $grandTotal);

                                            if (!is_numeric($state) || $state <= 0) {
                                                $set('paid_percentage', 0);
                                                $set('remaining_percentage', 100);
                                                return;
                                            }

                                            if ((float) $state > $remainingBalance) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Invalid Payment Amount')
                                                    ->body('Payment amount cannot exceed the remaining balance of Rs. ' . number_format($remainingBalance, 2))
                                                    ->danger()
                                                    ->send();

                                                $set('amount', null);
                                                $set('paid_percentage', 0);
                                                $set('remaining_percentage', 100);
                                                return;
                                            }

                                            $paymentAmount = (float)$state;

                                            // Corrected: % paid from remaining balance
                                            $rawPercentage = ($paymentAmount / $remainingBalance) * 100;
                                            $paidPercentage = self::cleanRounding($rawPercentage);
                                            $remainingPercentage = 100 - $paidPercentage;

                                            $set('paid_percentage', $paidPercentage);
                                            $set('remaining_percentage', $remainingPercentage);
                                        }),

                                    // Paid Percentage Display
                                    Placeholder::make('paid_percentage_display')
                                        ->label('Paid Percentage')
                                        ->content(function (Get $get) {
                                            $percentage = self::cleanRounding($get('paid_percentage', 0));
                                            return "{$percentage}%";
                                        }),

                                    // Remaining Percentage Display
                                    Placeholder::make('remaining_percentage_display')
                                        ->label('Remaining Percentage')
                                        ->content(function (Get $get) {
                                            $paid = self::cleanRounding($get('paid_percentage', 0));
                                            $remaining = 100 - $paid;
                                            return "{$remaining}%";
                                        }),

                                    // Hidden fields to store calculations
                                    Hidden::make('paid_percentage'),
                                    Hidden::make('remaining_percentage')
                                        ->default(100)
                                    ]),

                            Section::make('Payment Info')
                                ->columns(3)
                                ->schema([
                                    DatePicker::make('paid_date')
                                        ->label('Paid Date')
                                        ->required()
                                        ->default(now())
                                        ->minDate(function () {
                                            return auth()->user()->can('Allow Backdated Payments') ? null : now();
                                        })
                                        ->maxDate(function () {
                                            return auth()->user()->can('Allow Future Payments') ? null : now();
                                        })
                                        ->disabled(function () {
                                            return !auth()->user()->can('Allow Backdated Payments') && !auth()->user()->can('Allow Future Payments');
                                        })
                                        ->helperText(function () {
                                            if (!auth()->user()->can('Allow Backdated Payments') && !auth()->user()->can('Allow Future Payments')) {
                                                return 'You are restricted to select only today\'s date.';
                                            }

                                            if (!auth()->user()->can('Allow Future Payments')) {
                                                return 'You can select today and past dates only.';
                                            }

                                            if (!auth()->user()->can('Allow Backdated Payments')) {
                                                return 'You can select today and future dates only.';
                                            }

                                            return 'You can select any date.';
                                        }),

                                    Select::make('paid_via')
                                        ->label('Paid Via')
                                        ->options([
                                            'cash' => 'Cash',
                                            'card' => 'Card',
                                            'cheque' => 'Cheque',
                                            'other' => 'Other',
                                        ])
                                        ->default('cash') 
                                        ->required(),

                                    TextInput::make('payment_reference')
                                        ->label('Payment Reference')
                                        ->placeholder('e.g. Cheque no., Card ref., etc.')
                                        ->maxLength(255)
                                        ->nullable(),
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

    public static function cleanRounding(?float $value): float
    {
        if ($value === null) {
            return 0.0; // Or handle as needed
        }

        // Handle values very close to 0
        if (abs($value) < 0.0001) {
            return 0;
        }

        // Handle values very close to whole numbers
        $rounded = round($value, 2);
        $difference = abs($rounded - round($rounded));

        if ($difference < 0.0001) {
            return round($rounded);
        }

        return $rounded;
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Cus Invoice ID')->sortable()->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('order_type')->label('Order Type')->sortable(),
                TextColumn::make('order_id')->label('Order ID')->sortable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('customer_id')->label('Customer ID')->sortable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('amount')->label('Received Amount')->sortable()
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
                Action::make('Download Invoice')
                    ->icon('heroicon-o-arrow-down')
                    ->url(fn (CustomerAdvanceInvoice $record): string => route('customer-advance-invoice.pdf', ['invoice' => $record->id]))
                    ->openUrlInNewTab()
                    ->color('primary'),

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