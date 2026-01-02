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
                                            ->required()
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
                                                                $set('inventory_item_vat_group_id', $item->vatGroup->id);
                                                                $set('vat_group_name', $item->vatGroup->vat_group_name);
                                                                $set('vat_rate', $item->vatGroup->vat_rate); // ✅ FIXED
                                                            } else {
                                                                $set('inventory_item_vat_group_id', null);
                                                                $set('vat_group_name', null);
                                                                $set('vat_rate', null);
                                                            }

                                                            self::recalculate($set, $get);
                                                        })
                                                        ->required(),

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

                                                    TextInput::make('sub_total')
                                                        ->prefix('Rs.')
                                                        ->disabled(),

                                                    TextInput::make('vat_amount')
                                                        ->prefix('Rs.')
                                                        ->disabled(),

                                                    TextInput::make('total_with_vat')
                                                        ->prefix('Rs.')
                                                        ->disabled(),

                                                    Hidden::make('inventory_item_vat_group_id'),

                                                    Hidden::make('remaining_quantity')
                                                        ->default(fn ($get) => $get('quantity')),

                                                    Hidden::make('arrived_quantity')
                                                        ->default(0),
                                                ]),
                                            ])
                                            ->minItems(1)
                                            ->createItemButtonLabel('Add Order Item')
                                            ->columnSpan('full'),
                                    ]),
                                ]),

                        Tabs\Tab::make('VAT Information')
                            ->schema([
                                Section::make('Available VAT Groups')
                                    ->schema([
                                        TextInput::make('supplier_vat_rate')
                                            ->label('VAT Rate')
                                            ->suffix('%')
                                            ->disabled()
                                            ->dehydrated(false), 
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

        $set('sub_total', round($subTotal, 2));
        $set('vat_amount', round($vat, 2));
        $set('total_with_vat', round($subTotal + $vat, 2));
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
                TextColumn::make('wanted_date')->label('Wanted Date')->date(),
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