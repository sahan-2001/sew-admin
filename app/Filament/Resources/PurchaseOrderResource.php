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

    protected static ?string $navigationGroup = 'Orders';
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
                                    ->columns(2)
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

                                                            self::recalculate($set, $get);
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
                                                            self::recalculate($set, $get)
                                                        ),

                                                    TextInput::make('price')
                                                        ->numeric()
                                                        ->reactive()
                                                        ->required()
                                                        ->prefix('Rs.')
                                                        ->afterStateUpdated(fn ($state, $set, $get) =>
                                                            self::recalculate($set, $get)
                                                        ),

                                                    TextInput::make('vat_rate')
                                                        ->label('Item VAT %')
                                                        ->suffix('%')
                                                        ->disabled()
                                                        ->reactive()
                                                        ->dehydrated(false),

                                                    // FIXED: Changed field names to match model
                                                    TextInput::make('item_subtotal')  // Changed from 'sub_total'
                                                        ->prefix('Rs.')
                                                        ->disabled()
                                                        ->dehydrated(true),

                                                    TextInput::make('item_vat_amount')  // Changed from 'vat_amount'
                                                        ->prefix('Rs.')
                                                        ->disabled()
                                                        ->dehydrated(true),

                                                    TextInput::make('item_grand_total')  // Changed from 'total_with_vat'
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
                                            Grid::make(3)->schema([
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
                            ]),
                    ])
                    ->columnSpan('full'),
            ]);
    }

    protected static function recalculate(callable $set, callable $get): void
    {
        $qty   = (float) $get('quantity');
        $price = (float) $get('price');
        $rate  = (float) $get('vat_rate');

        $subTotal = $qty * $price;
        $vat      = ($subTotal * $rate) / 100;
        $total    = $subTotal + $vat;

        // Set hidden fields that WILL be saved
        $set('item_subtotal', round($subTotal, 2));      
        $set('item_vat_amount', round($vat, 2));         
        $set('item_grand_total', round($total, 2));      
        
        // Set display fields (not saved)
        $set('display_subtotal', round($subTotal, 2));   // Display only
        $set('display_vat_amount', round($vat, 2));      
        $set('display_grand_total', round($total, 2));   
        
        // Recalculate both summaries
        self::calculateSummary($set, $get);
        self::recalculateFinalSummary($set, $get);
    }

    protected static function calculateSummary(callable $set, callable $get): void
    {
        $items = $get('items');
        
        $subTotalSum = 0;
        $vatSum = 0;
        $totalWithVatSum = 0;
        
        if (is_array($items)) {
            foreach ($items as $item) {
                // FIXED: Using correct field names
                $subTotalSum += (float) ($item['item_subtotal'] ?? 0);       
                $vatSum += (float) ($item['item_vat_amount'] ?? 0);           
                $totalWithVatSum += (float) ($item['item_grand_total'] ?? 0);
            }
        }
        
        $set('items_sub_total_sum', round($subTotalSum, 2));
        $set('items_vat_sum', round($vatSum, 2));
        $set('items_total_with_vat_sum', round($totalWithVatSum, 2));
        
        // Also update display fields in VAT tab
        $set('display_sub_total_sum', round($subTotalSum, 2));
        $set('display_vat_sum', round($vatSum, 2));
    }

    protected static function recalculateFinalSummary(callable $set, callable $get): void
    {
        $subTotal     = (float) $get('items_sub_total_sum');
        $itemVat      = (float) $get('items_vat_sum');
        $itemGrand    = (float) $get('items_total_with_vat_sum');
        $supplierRate = (float) $get('supplier_vat_rate');
        $vatBase      = $get('vat_base');

        $supplierVat   = round(($subTotal * $supplierRate) / 100, 2);
        $supplierTotal = round($subTotal + $supplierVat, 2);

        if ($vatBase === 'item_vat') {
            $set('final_vat_amount', $itemVat);
            $set('final_grand_total', $itemGrand);
        }

        if ($vatBase === 'supplier_vat') {
            $set('final_vat_amount', $supplierVat);
            $set('final_grand_total', $supplierTotal);
        }
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['order_subtotal'] = $data['items_sub_total_sum'] ?? 0;
        $data['vat_amount']     = $data['items_vat_sum'] ?? 0;
        $data['grand_total']    = $data['items_total_with_vat_sum'] ?? 0;
        $data['vat_base']       = $data['vat_base'] ?? 'item_vat';

        return $data;
    }

    protected static function mutateFormDataBeforeSave(array $data): array
    {
        $data['order_subtotal'] = $data['items_sub_total_sum'] ?? 0;
        $data['vat_amount']     = $data['items_vat_sum'] ?? 0;
        $data['grand_total']    = $data['items_total_with_vat_sum'] ?? 0;
        $data['vat_base']       = $data['vat_base'] ?? 'item_vat';

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Order ID')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('supplier_id')->label('Supplier ID')->searchable(),
                TextColumn::make('wanted_delivery_date')->label('Wanted Delivery Date')->date(),
                TextColumn::make('promised_delivery_date')->label('Promised Delivery Date')->date(),
                TextColumn::make('vat_base')->label('VAT Base')->sortable(),
                TextColumn::make('status')->label('Status')
                    ->badge()
                    ->colors([
                        'planned' => 'gray',
                        'released' => 'blue',
                        'cancelled' => 'red',
                        'completed' => 'green',
                    ]),
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
                SelectFilter::make('provider_type')
                    ->label('Provider Type')
                    ->options([
                        'supplier' => 'Supplier',
                        'customer' => 'Customer',
                    ])
                    ->placeholder('All Types'),

                Filter::make('wanted_date')
                    ->label('Wanted Delivery Date')
                    ->form([
                        DatePicker::make('date')
                            ->label('Wanted Delivery Date')
                            ->closeOnDateSelection(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when($data['date'], fn ($q, $date) =>
                            $q->whereDate('wanted_date', $date)
                        );
                    }),
            ])
            ->actions([
                Action::make('handle')
                    ->label('Handle')
                    ->url(fn ($record) => PurchaseOrderResource::getUrl('handle', ['record' => $record]))
                    ->openUrlInNewTab(false),

                EditAction::make()
                    ->visible(fn ($record) => 
                        auth()->user()->can('edit purchase orders') &&
                        $record->status === 'planned'
                    ),

                DeleteAction::make()
                    ->visible(fn ($record) => 
                        auth()->user()->can('delete purchase orders') &&
                        $record->status === 'planned'
                    ),
                
            ])
            ->defaultSort('id', 'desc')
            ->recordUrl(null);
    }




    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
            'handle' => Pages\HandlePurchaseOrder::route('/{record}/handle'),
        ];
    }
}