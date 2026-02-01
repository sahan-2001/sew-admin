<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\Customer;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Tabs;
use Illuminate\Support\Carbon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationGroup = 'Purchase Orders';
    protected static ?string $navigationLabel = 'Purchase Orders';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-on-square';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('view purchase orders') ?? false;
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Purchase Order')
                    ->tabs([
                        Tabs\Tab::make('Order Details')
                            ->schema([
                                Section::make('Order Information')
                                    ->schema([
                                        Select::make('supplier_id')
                                            ->label('Supplier')
                                            ->options(function () {
                                                return Supplier::all()
                                                    ->mapWithKeys(fn ($supplier) => [
                                                        $supplier->supplier_id =>
                                                            "Supplier ID - {$supplier->supplier_id} | Name - {$supplier->name}"
                                                    ])
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->reactive()
                                            ->required()
                                            ->disabled(fn (string $operation) => $operation === 'edit')
                                            ->afterStateUpdated(function ($state, callable $set) {

                                                $supplier = Supplier::with('vatGroup')->find($state);

                                                if ($supplier) {
                                                    // Basic supplier info
                                                    $set('supplier_name', $supplier->name);
                                                    $set('supplier_email', $supplier->email);
                                                    $set('supplier_phone', $supplier->phone_1);

                                                    // VAT group info
                                                    if ($supplier->vatGroup) {
                                                        $set('supplier_vat_group_id', $supplier->vatGroup->id);
                                                        $set('supplier_vat_group_name', $supplier->vatGroup->vat_group_name);
                                                        $set('supplier_vat_rate', $supplier->vatGroup->vat_rate);
                                                    } else {
                                                        $set('supplier_vat_group_id', null);
                                                        $set('supplier_vat_group_name', null);
                                                        $set('supplier_vat_rate', null);
                                                    }
                                                }
                                            }),

                                        TextInput::make('supplier_name')
                                            ->label('Name')
                                            ->disabled(),

                                        TextInput::make('supplier_email')
                                            ->label('Email')
                                            ->disabled(),

                                        TextInput::make('supplier_phone')
                                            ->label('Phone')
                                            ->disabled(),

                                        Hidden::make('user_id')
                                            ->default(fn () => auth()->id()),

                                        TextInput::make('supplier_vat_group_name')
                                            ->label('Supplier VAT Group')
                                            ->disabled()
                                            ->dehydrated(false),

                                        TextInput::make('supplier_vat_rate')
                                            ->label('Supplierr VAT Rate')
                                            ->suffix('%')
                                            ->disabled()
                                            ->dehydrated(false),

                                        Hidden::make('supplier_vat_group_id'),
                                        Hidden::make('supplier_vat_rate'),
                                    ])
                                    ->columns(2),
                                    
                                Section::make('Order Information')
                                    ->schema([
                                        DatePicker::make('wanted_delivery_date')
                                            ->label('Wanted Delivery Date')
                                            ->required()
                                            ->minDate(now()),
                                        
                                        DatePicker::make('promised_delivery_date')
                                            ->label('Promised Delivery Date')
                                            ->minDate(now()),
                                            
                                        Textarea::make('special_note')
                                            ->label('Special Note')
                                            ->nullable()
                                            ->columnSpan(2), 

                                        Hidden::make('supplier_vat_group_id'),
                                    ])
                                    ->columns(2),

                            Section::make('Terms & Methods')
                                ->columns(2)
                                ->schema([
                                    Select::make('currency_code_id')
                                        ->label('Currency')
                                        ->options(
                                            fn () => \App\Models\Currency::where('is_active', true)
                                                ->get()
                                                ->mapWithKeys(fn ($c) => [
                                                    $c->id => "{$c->code} | {$c->name}"
                                                ])
                                                ->toArray()
                                        )
                                        ->searchable()
                                        ->preload(),

                                    Select::make('payment_term_id')
                                        ->label('Payment Terms')
                                        ->options(
                                            fn () => \App\Models\PaymentTerm::get()
                                                ->mapWithKeys(fn ($p) => [
                                                    $p->id => "{$p->name} | {$p->description}"
                                                ])
                                                ->toArray()
                                        )
                                        ->searchable()
                                        ->preload(),

                                    Select::make('delivery_term_id')
                                        ->label('Delivery Terms')
                                        ->options(
                                            fn () => \App\Models\DeliveryTerm::get()
                                                ->mapWithKeys(fn ($d) => [
                                                    $d->id => "{$d->name} | {$d->description}"
                                                ])
                                                ->toArray()
                                        )
                                        ->searchable()
                                        ->preload(),

                                    Select::make('delivery_method_id')
                                        ->label('Delivery Method')
                                        ->options(
                                            fn () => \App\Models\DeliveryMethod::get()
                                                ->mapWithKeys(fn ($m) => [
                                                    $m->id => "{$m->name} | {$m->description}"
                                                ])
                                                ->toArray()
                                        )
                                        ->searchable()
                                        ->preload(),
                                ]),

                            ]),

                        Tabs\Tab::make('Order Items')
                            ->schema([
                                Section::make('Items')
                                    ->schema([
                                        Repeater::make('items')
                                            ->relationship('items')
                                            ->schema([
                                                Grid::make(3)->schema([
                                                    Select::make('inventory_item_id')
                                                        ->label('Item')
                                                        ->relationship('inventoryItem', 'name')
                                                        ->searchable()
                                                        ->preload()
                                                        ->reactive()
                                                        ->columnSpan(2)
                                                        ->getOptionLabelFromRecordUsing(fn ($record) =>
                                                            "{$record->id} | Item Code - {$record->item_code} | Name - {$record->name}"
                                                        )
                                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                            $item = InventoryItem::with('vatGroup')->find($state);

                                                            if ($item && $item->vatGroup) {
                                                                // 🔒 SNAPSHOT VALUES (SAVED)
                                                                $set('inventory_vat_group_id', $item->vatGroup->id);
                                                                $set('inventory_vat_rate', $item->vatGroup->vat_rate);

                                                                // 🖥 DISPLAY ONLY
                                                                $set('vat_group_name', $item->vatGroup->vat_group_name);
                                                                $set('vat_rate', $item->vatGroup->vat_rate);
                                                            } else {
                                                                $set('inventory_vat_group_id', null);
                                                                $set('inventory_vat_rate', 0);
                                                                $set('vat_group_name', null);
                                                                $set('vat_rate', 0);
                                                            }

                                                            self::recalculateItem($set, $get);
                                                            self::calculateSummary($set, $get);
                                                        }),

                                                    Hidden::make('inventory_vat_group_id'),
                                                    Hidden::make('inventory_vat_rate'),

                                                    TextInput::make('vat_group_name')
                                                        ->label('Item VAT Group')
                                                        ->disabled()
                                                        ->dehydrated(false),

                                                    TextInput::make('quantity')
                                                        ->numeric()
                                                        ->reactive()
                                                        ->required()
                                                        ->afterStateUpdated(fn ($state, $set, $get) =>
                                                            self::recalculateItem($set, $get)
                                                        ),

                                                    TextInput::make('price')
                                                        ->numeric()
                                                        ->reactive()
                                                        ->required()
                                                        ->prefix('Rs.')
                                                        ->afterStateUpdated(fn ($state, $set, $get) =>
                                                            self::recalculateItem($set, $get)
                                                        ),

                                                    TextInput::make('vat_rate')
                                                        ->label('Item VAT %')
                                                        ->suffix('%')
                                                        ->disabled()
                                                        ->reactive()
                                                        ->dehydrated(false),

                                                    // FIXED: Changed field names to match model
                                                    TextInput::make('item_subtotal') 
                                                        ->prefix('Rs.')
                                                        ->disabled()
                                                        ->dehydrated(true),

                                                    TextInput::make('line_discount')
                                                        ->label('Discount')
                                                        ->numeric()
                                                        ->reactive()
                                                        ->default(0)
                                                        ->prefix('Rs.')
                                                        ->afterStateUpdated(fn ($state, $set, $get) => [
                                                            self::recalculateItem($set, $get),
                                                            self::calculateSummary($set, $get),
                                                        ]),

                                                    TextInput::make('item_vat_amount') 
                                                        ->prefix('Rs.')
                                                        ->disabled()
                                                        ->dehydrated(true),
                                                        
                                                    TextInput::make('item_grand_total')  
                                                        ->prefix('Rs.')
                                                        ->disabled()
                                                        ->dehydrated(true),

                                                    Hidden::make('remaining_quantity')
                                                        ->default(fn ($get) => $get('quantity')),

                                                    Hidden::make('arrived_quantity')
                                                        ->default(0),
                                                ]),
                                            ])
                                            ->minItems(1)
                                            ->createItemButtonLabel('Add Order Item')
                                            ->columnSpan('full')
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                self::calculateSummary($set, $get);
                                            }),
                                    ]),
                                
                                // SUMMARY SECTION - ADDED HERE
                                Section::make('Order Summary')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Grid::make(4)->schema([
                                                TextInput::make('items_sub_total_sum')
                                                    ->label('Sub Total of all Items')
                                                    ->prefix('Rs.')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->default(0.00),
                                                
                                                TextInput::make('items_vat_sum')
                                                    ->label('VAT Total')
                                                    ->prefix('Rs.')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->default(0.00),

                                                TextInput::make('items_discount_sum')
                                                    ->label('Total Discount')
                                                    ->prefix('Rs.')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->default(0.00),

                                                TextInput::make('items_total_with_vat_sum')
                                                    ->label('Grand Total of all Items')
                                                    ->prefix('Rs.')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->default(0.00),
                                            ])->columnSpan(2),
                                            
                                            \Filament\Forms\Components\Actions::make([
                                                \Filament\Forms\Components\Actions\Action::make('refreshSummary')
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->color('gray')
                                                    ->action(function (callable $set, callable $get) {
                                                        self::calculateSummary($set, $get);
                                                    })
                                                    ->tooltip('Refresh summary')
                                            ])->columnSpan(1)->alignEnd(),
                                        ]),
                                    ])
                                    ->columns(1),
                            ]),

                        Tabs\Tab::make('VAT Information')
                            ->schema([

                                Section::make('Available VAT Groups')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('items_sub_total_sum')
                                            ->label('Sub Total of all Items')
                                            ->prefix('Rs.')
                                            ->disabled()
                                            ->dehydrated(),

                                        TextInput::make('supplier_vat_rate')
                                            ->label('Supplier VAT Rate')
                                            ->suffix('%')
                                            ->disabled()
                                            ->dehydrated(),

                                        TextInput::make('items_vat_sum')
                                            ->label('Item-wise VAT Total')
                                            ->prefix('Rs.')
                                            ->disabled()
                                            ->dehydrated(),
                                    ]),

                                Section::make('Select Wanted VAT Base')
                                    ->schema([
                                        Select::make('vat_base')
                                            ->label('VAT Base')
                                            ->options([
                                                'supplier_vat' => 'Supplier VAT Rate (%)',
                                                'item_vat'     => 'Item-wise VAT Rate (%)',
                                            ])
                                            ->default('item_vat')
                                            ->required()
                                            ->reactive()
                                            ->disabled(fn ($record) => $record !== null)
                                            ->afterStateUpdated(fn (callable $set, callable $get) =>
                                                self::recalculateFinalSummary($set, $get)
                                            ),
                                    ]),

                                Section::make('Final Order Summary Based on Selected VAT Base')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('items_sub_total_sum')
                                            ->label('Sub Total')
                                            ->prefix('Rs.')
                                            ->disabled()
                                            ->dehydrated(false),

                                        // ITEM Based VAT (saved when item_vat selected)
                                        TextInput::make('items_vat_sum')
                                            ->label('Item VAT Amount')
                                            ->prefix('Rs.')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->visible(fn (callable $get) => $get('vat_base') === 'item_vat'),

                                        TextInput::make('items_total_with_vat_sum')
                                            ->label('Item Grand Total')
                                            ->prefix('Rs.')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->visible(fn (callable $get) => $get('vat_base') === 'item_vat'),
            
                                        // SUPPLIER Baced VAT
                                        TextInput::make('final_vat_amount')
                                            ->label('Supplier Based VAT Amount')
                                            ->prefix('Rs.')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->visible(fn (callable $get) => $get('vat_base') === 'supplier_vat'),

                                        TextInput::make('final_grand_total')
                                            ->label('Supplier Based Grand Total')
                                            ->prefix('Rs.')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->visible(fn (callable $get) => $get('vat_base') === 'supplier_vat'),
                                    ]),

                                Section::make('Final Order Summary')
                                    ->columns(4)
                                    ->schema([

                                        Select::make('order_discount_type')
                                            ->label('Discount Type')
                                            ->options([
                                                'amount' => 'Fixed Amount',
                                                'percent' => 'Percentage (%)',
                                            ])
                                            ->default('amount')
                                            ->reactive()
                                            ->afterStateUpdated(fn ($set, $get) =>
                                                self::recalculateFinalSummary($set, $get)
                                            ),

                                        TextInput::make('order_discount_value')
                                            ->label('Discount Value')
                                            ->numeric()
                                            ->default(0)
                                            ->reactive()
                                            ->afterStateUpdated(fn ($set, $get) =>
                                                self::recalculateFinalSummary($set, $get)
                                            ),

                                        TextInput::make('order_discount_amount')
                                            ->label('Final Discount Amount')
                                            ->prefix('Rs.')
                                            ->disabled()
                                            ->default(0)
                                            ->dehydrated(false),

                                        TextInput::make('final_grand_total')
                                            ->label('FINAL ORDER TOTAL')
                                            ->prefix('Rs.')
                                            ->disabled()
                                            ->reactive()
                                            ->dehydrated(true),
                                    ]),
                            ]),
                    ])
                    ->columnSpan('full'),
            ]);
    }

    /* -----------------------------------------------------------------
     |  CALCULATIONS
     | -----------------------------------------------------------------
     */
    protected static function recalculateItem(callable $set, callable $get): void
    {
        $qty        = (float) ($get('quantity') ?? 0);
        $price      = (float) ($get('price') ?? 0);
        $vatRate    = (float) ($get('vat_rate') ?? 0);
        $discount   = (float) ($get('line_discount') ?? 0);

        $subTotal   = round($qty * $price, 2);
        $subAfterDis = max($subTotal - $discount, 0);

        $vatAmount  = round(($subAfterDis * $vatRate) / 100, 2);
        $grandTotal = round($subAfterDis + $vatAmount, 2);

        $set('item_subtotal', $subTotal);
        $set('item_vat_amount', $vatAmount);
        $set('item_grand_total', $grandTotal);
    }

    protected static function calculateSummary(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];

        $subTotal = collect($items)->sum(fn ($i) => (float) ($i['item_subtotal'] ?? 0));
        $vatTotal = collect($items)->sum(fn ($i) => (float) ($i['item_vat_amount'] ?? 0));
        $discount = collect($items)->sum(fn ($i) => (float) ($i['line_discount'] ?? 0));
        $grand    = collect($items)->sum(fn ($i) => (float) ($i['item_grand_total'] ?? 0));

        $set('items_sub_total_sum', round($subTotal, 2));
        $set('items_vat_sum', round($vatTotal, 2));
        $set('items_discount_sum', round($discount, 2));
        $set('items_total_with_vat_sum', round($grand, 2));

        // ✅ THIS WAS MISSING
        self::recalculateFinalSummary($set, $get);
    }

    protected static function recalculateFinalSummary(callable $set, callable $get): void
    {
        $subTotal = (float) ($get('items_sub_total_sum') ?? 0);
        $itemVat  = (float) ($get('items_vat_sum') ?? 0);

        $vatBase  = $get('vat_base');
        $supRate  = (float) ($get('supplier_vat_rate') ?? 0);

        $vatAmount = $vatBase === 'supplier_vat'
            ? round(($subTotal * $supRate) / 100, 2)
            : round($itemVat, 2);

        $gross = round($subTotal + $vatAmount, 2);

        $discType = $get('order_discount_type');
        $discVal  = (float) ($get('order_discount_value') ?? 0);

        $discount = $discType === 'percent'
            ? round(($gross * $discVal) / 100, 2)
            : round($discVal, 2);

        $discount = min($discount, $gross);

        $final = round($gross - $discount, 2);

        // ✅ THIS WAS MISSING
        $set('order_discount_amount', $discount);
        $set('final_vat_amount', $vatAmount);
        $set('final_grand_total', $final);
    }

    protected static function recalculate(callable $set, callable $get): void
    {
        self::recalculateItem($set, $get);
        self::calculateSummary($set, $get);
    }


    /* -----------------------------------------------------------------
     |  PAGES
     | -----------------------------------------------------------------
     */
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit'   => Pages\EditPurchaseOrder::route('/{record}/edit'),
            'handle' => Pages\HandlePurchaseOrder::route('/{record}/handle'),
        ];
    }
}
