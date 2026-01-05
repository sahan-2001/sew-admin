<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseQuotationResource\Pages;
use App\Models\PurchaseQuotation;
use App\Models\InventoryItem;
use App\Models\Supplier;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Forms\Components\{TextInput,Select,DatePicker,Textarea,Repeater,Hidden,Section,Grid,Tabs};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\{EditAction,DeleteAction,Action};
use Illuminate\Support\Facades\Auth;

class PurchaseQuotationResource extends Resource
{
    protected static ?string $model = PurchaseQuotation::class;

    protected static ?string $navigationGroup = 'Orders';
    protected static ?string $navigationLabel = 'Purchase Quotations';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('view purchase quotations') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Purchase Quotation')
                ->tabs([
                    Tabs\Tab::make('Quotation Details')
                        ->schema([
                            Section::make('Supplier Information')
                                ->columns(2)
                                ->schema([
                                    DatePicker::make('quotation_date')
                                        ->label('Quotation Date')
                                        ->default(now())
                                        ->disabled(),

                                    DatePicker::make('valid_until')
                                        ->label('Valid Until')
                                        ->mindate(now())
                                        ->required(),

                                    Section::make('Supplier')
                                        ->columns(3)
                                        ->schema([
                                            Select::make('supplier_id')
                                                ->label('Supplier')
                                                ->relationship('supplier', 'name')
                                                ->searchable()
                                                ->preload()
                                                ->live()
                                                ->getOptionLabelFromRecordUsing(fn(Supplier $r) => "{$r->supplier_id} | {$r->name}")
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    $supplier = Supplier::with('vatGroup')->find($state);
                                                    if ($supplier) {
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

                                            TextInput::make('supplier_name')->disabled(),
                                            TextInput::make('supplier_email')->disabled(),
                                            TextInput::make('supplier_vat_group_name')
                                                ->label('Supplier VAT Group')
                                                ->disabled()
                                                ->dehydrated(false),
                                            TextInput::make('supplier_vat_rate')
                                                ->label('Supplier VAT Rate')
                                                ->suffix('%')
                                                ->disabled()
                                                ->dehydrated(false),
                                            Hidden::make('supplier_vat_group_id'),
                                            Hidden::make('supplier_vat_rate'),
                                        ]),
                                ]),

                            Section::make('Quotation Meta')
                                ->columns(2)
                                ->schema([
                                    DatePicker::make('wanted_delivery_date')->label('Expected Delivery Date')->mindate(now())->dehydrated(true),
                                    DatePicker::make('promised_delivery_date')->label('Promised Delivery Date')->mindate(now())->dehydrated(true),
                                    Textarea::make('special_note')->label('Remarks')->columnSpan(2),
                                    Hidden::make('status')->default('draft'),
                                ]),
                        ]),

                    Tabs\Tab::make('Quotation Items')
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
                                                                $set('inventory_vat_group_id', $item->vatGroup->id);
                                                                $set('inventory_vat_rate', $item->vatGroup->vat_rate);

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

                                                    TextInput::make('item_subtotal') 
                                                        ->prefix('Rs.')
                                                        ->disabled()
                                                        ->dehydrated(true),

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

        $set('item_subtotal', round($subTotal, 2));
        $set('item_vat_amount', round($vat, 2));
        $set('item_grand_total', round($total, 2));

        self::calculateSummary($set, $get);
        self::recalculateFinalSummary($set, $get);
    }

    protected static function calculateSummary(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];

        $subTotalSum = collect($items)->sum(fn ($i) => (float) ($i['item_subtotal'] ?? 0));
        $vatSum      = collect($items)->sum(fn ($i) => (float) ($i['item_vat_amount'] ?? 0));
        $grandTotal  = collect($items)->sum(fn ($i) => (float) ($i['item_grand_total'] ?? 0));

        $set('items_sub_total_sum', round($subTotalSum, 2));
        $set('items_vat_sum', round($vatSum, 2));
        $set('items_total_with_vat_sum', round($grandTotal, 2));
    }

    protected static function recalculateFinalSummary(callable $set, callable $get): void
    {
        $subTotal     = (float) $get('items_sub_total_sum');
        $itemVat      = (float) $get('items_vat_sum');
        $supplierRate = (float) ($get('supplier_vat_rate') ?? 0);
        $vatBase      = $get('vat_base');

        if ($vatBase === 'item_vat') {
            $set('final_vat_amount', $itemVat);
            $set('final_grand_total', $subTotal + $itemVat);
        } elseif ($vatBase === 'supplier_vat') {
            $supplierVat = round(($subTotal * $supplierRate) / 100, 2);
            $set('final_vat_amount', $supplierVat);
            $set('final_grand_total', round($subTotal + $supplierVat, 2));
        }
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['order_subtotal'] = $data['items_sub_total_sum'] ?? 0;
        $data['vat_amount']     = $data['final_vat_amount'] ?? 0;
        $data['grand_total']    = $data['final_grand_total'] ?? 0;
        $data['vat_base']       = $data['vat_base'] ?? 'item_vat';
        return $data;
    }

    protected static function mutateFormDataBeforeSave(array $data): array
    {
        return self::mutateFormDataBeforeCreate($data);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Quotation No')->sortable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable(),
                TextColumn::make('grand_total')->money('LKR'),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'draft' => 'gray',
                        'sent' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn ($record) => $record->status === 'draft'),

                Action::make('print_pdf')
                    ->label('Print PDF')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->visible(fn ($record) => true) 
                    ->url(fn ($record) => route('purchase-quotation.pdf', $record)) 
                    ->openUrlInNewTab(),

                DeleteAction::make()
                    ->visible(fn ($record) => $record->status === 'draft'),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPurchaseQuotations::route('/'),
            'create' => Pages\CreatePurchaseQuotation::route('/create'),
            'edit'   => Pages\EditPurchaseQuotation::route('/{record}/edit'),
        ];
    }
}
