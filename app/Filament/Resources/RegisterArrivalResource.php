<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegisterArrivalResource\Pages;
use App\Models\RegisterArrival;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use Filament\Forms;
use Filament\Forms\Components\{TextInput, DatePicker, Select, Textarea, FileUpload, Grid, Section, Repeater};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\{TextColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;

class RegisterArrivalResource extends Resource
{
    protected static ?string $model = RegisterArrival::class;
    protected static ?string $navigationGroup = 'Warehouse Management';
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            // Section 1: Purchase Order Details
            Section::make('Purchase Order Details')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('purchase_order_id')
                                ->label('Purchase Order ID')
                                ->nullable()
                                ->reactive()
                                ->afterStateUpdated(fn ($state, Set $set) => static::loadPurchaseOrderDetails($state, $set)),

                            TextInput::make('provider_name')
                                ->label('Provider Name')
                                ->disabled()
                                ->hidden(fn (Get $get) => !$get('purchase_order_id')),

                            TextInput::make('provider_phone')
                                ->label('Provider Phone')
                                ->disabled()
                                ->hidden(fn (Get $get) => !$get('purchase_order_id')),

                            TextInput::make('due_date')
                                ->label('Due Date')
                                ->disabled()
                                ->hidden(fn (Get $get) => !$get('purchase_order_id')),
                        ]),
                ]),

            // Section 2: Purchase Order Items
            Section::make('Purchase Order Items')
                ->schema([
                    Repeater::make('purchase_order_items')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('item_id')
                                        ->label('Item')
                                        ->relationship('inventoryItem', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->options(fn () => InventoryItem::all()->pluck('name', 'id'))
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            $item = InventoryItem::find($state);
                                            if ($item) {
                                                $set('item_code', $item->item_code);
                                                $set('item_name', $item->name);
                                            }
                                        }),

                                    TextInput::make('item_code')->label('Item Code')->disabled(),
                                    TextInput::make('item_name')->label('Item Name')->disabled(),
                                ]),
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('quantity')
                                        ->label('Quantity')
                                        ->reactive()
                                        ->required(),

                                    TextInput::make('price')
                                        ->label('Price')
                                        ->reactive()
                                        ->required()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $quantity = $get('quantity');
                                            if ($quantity > 0) {
                                                $set('total', $state * $quantity);
                                            }
                                        }),

                                    TextInput::make('total')
                                        ->label('Total')
                                        ->reactive()
                                        ->disabled()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $price = $get('price');
                                            if ($price > 0) {
                                                $set('quantity', $state / $price);
                                            }
                                        }),
                                ]),
                        ])
                        ->disableItemCreation(false)
                        ->relationship('items')
                        ->createItemButtonLabel('Add Item')
                        ->afterStateHydrated(function ($state, $get, $set) {
                            $purchaseOrderId = $get('purchase_order_id');

                            if ($purchaseOrderId) {
                                $purchaseOrder = PurchaseOrder::find($purchaseOrderId);

                                if ($purchaseOrder && in_array($purchaseOrder->status, ['released', 'partially arrived'])) {
                                    $items = PurchaseOrderItem::where('purchase_order_id', $purchaseOrderId)
                                        ->with('inventoryItem')
                                        ->get();

                                    $itemsData = $items->map(function ($item) {
                                        return [
                                            'item_id' => $item->inventoryItem->id,
                                            'item_code' => $item->inventoryItem->item_code,
                                            'item_name' => $item->inventoryItem->name,
                                            'quantity' => $item->remaining_quantity ?? $item->quantity,
                                            'price' => $item->price,
                                            'total' => ($item->remaining_quantity ?? $item->quantity) * $item->price,
                                        ];
                                    })->toArray();

                                    $set('purchase_order_items', $itemsData);
                                } else {
                                    Notification::make()
                                        ->title('Invalid Purchase Order')
                                        ->danger()
                                        ->body('The selected purchase order is not in a released or partially arrived status.')
                                        ->send();

                                    $set('purchase_order_items', []);
                                }
                            } else {
                                $set('purchase_order_items', []);
                            }
                        }),
                ]),

            // Section 3: Arrival Information and Invoice Upload
            Section::make('Arrival Information and Invoice Upload')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('location_id')
                                ->label('Location')
                                ->relationship('location', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->options(fn () => InventoryLocation::orderByRaw("CASE WHEN location_type = 'arrival' THEN 1 ELSE 2 END")
                                    ->pluck('name', 'id')),

                            DatePicker::make('received_date')
                                ->label('Received Date')
                                ->default(now())
                                ->required(),

                            TextInput::make('invoice_number')
                                ->label('Invoice Number')
                                ->required(),

                            FileUpload::make('image_of_invoice')
                                ->label('Invoice Image')
                                ->image()
                                ->nullable(),

                            Textarea::make('note')
                                ->label('Notes')
                                ->nullable(),
                        ]),
                ]),
        ]);
    }

    public static function loadPurchaseOrderDetails($purchaseOrderId, Set $set)
    {
        if (!$purchaseOrderId) {
            $set('provider_name', null);
            $set('provider_phone', null);
            $set('due_date', null);
            $set('purchase_order_items', []);
            return;
        }

        $purchaseOrder = PurchaseOrder::find($purchaseOrderId);

        if ($purchaseOrder && in_array($purchaseOrder->status, ['released', 'partially arrived'])) {
            $set('provider_name', $purchaseOrder->provider_name);
            $set('provider_phone', $purchaseOrder->provider_phone);
            $set('due_date', $purchaseOrder->wanted_date);

            $items = PurchaseOrderItem::where('purchase_order_id', $purchaseOrderId)
                ->with('inventoryItem')
                ->get();

            $itemsData = $items->map(function ($item) {
                return [
                    'item_id' => $item->inventoryItem->id,
                    'item_code' => $item->inventoryItem->item_code,
                    'item_name' => $item->inventoryItem->name,
                    'remaining_quantity' => $item->remaining_quantity ?? $item->quantity,
                    'arrived_quantity' => $item->arrived_quantity ?? 0,
                    'price' => $item->price,
                    'total' => ($item->remaining_quantity ?? $item->quantity) * $item->price,
                ];
            })->toArray();

            $set('purchase_order_items', $itemsData);
        } else {
            Notification::make()
                ->title('Invalid Purchase Order')
                ->danger()
                ->body('The selected purchase order is not in a released or partially arrived status.')
                ->send();

            $set('provider_name', null);
            $set('provider_phone', null);
            $set('due_date', null);
            $set('purchase_order_items', []);
        }
    }

    public static function updatePurchaseOrderStatusAndItems($registerArrival)
    {
        $purchaseOrderId = $registerArrival->purchase_order_id;

        if ($purchaseOrderId) {
            foreach ($registerArrival->items as $item) {
                $purchaseOrderItem = PurchaseOrderItem::where('purchase_order_id', $purchaseOrderId)
                    ->where('inventory_item_id', $item->item_id)
                    ->first();

                if ($purchaseOrderItem) {
                    $purchaseOrderItem->arrived_quantity = $purchaseOrderItem->arrived_quantity ?? 0;
                    $purchaseOrderItem->remaining_quantity = $purchaseOrderItem->remaining_quantity ?? $purchaseOrderItem->quantity;

                    $purchaseOrderItem->arrived_quantity += $item->quantity;
                    $purchaseOrderItem->remaining_quantity = max(0, $purchaseOrderItem->remaining_quantity - $item->quantity);

                    $purchaseOrderItem->save();
                }
            }

            $allItems = PurchaseOrderItem::where('purchase_order_id', $purchaseOrderId)->get();
            $allRemainingZero = $allItems->every(fn ($item) => $item->remaining_quantity === 0);

            $purchaseOrder = PurchaseOrder::find($purchaseOrderId);
            if ($purchaseOrder) {
                $purchaseOrder->status = $allRemainingZero ? 'arrived' : 'partially arrived';
                $purchaseOrder->save();
            }
        }
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->searchable(),
                TextColumn::make('purchase_order_id')->sortable()->searchable(),
                TextColumn::make('invoice_number')->sortable()->searchable(),
                TextColumn::make('received_date')->sortable()->searchable(),
                TextColumn::make('location_id')->sortable(),
                TextColumn::make('location.name')->sortable(),
            ])
            ->filters([
                SelectFilter::make('purchase_order_id')
                    ->label('Purchase Order')
                    ->relationship('purchaseOrder', 'id'),
            ])
            ->defaultSort('received_date', 'desc')
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegisterArrivals::route('/'),
            'create' => Pages\CreateRegisterArrival::route('/create'),
            'edit' => Pages\EditRegisterArrival::route('/{record}/edit'),
        ];
    }
}