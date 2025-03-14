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
                    Grid::make(2) // Two-column layout for this section
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
                    Repeater::make('purchase_order_items') // Repeater component to show PO items
                        ->schema([
                            Grid::make(2) // Two-column layout for each item
                                ->schema([
                                    Select::make('item_id')
                                        ->label('Item')
                                        ->relationship('inventoryItem', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->options(function () {
                                            return InventoryItem::all()->pluck('name', 'id');
                                        })
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
                            Grid::make(3) // Three-column layout for each item
                                ->schema([
                                    TextInput::make('quantity')
                                        ->label('Quantity')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $price = $get('price');
                                            $set('total', $state * $price);
                                        }),
                                    TextInput::make('price')
                                        ->label('Price')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            $quantity = $get('quantity');
                                            $set('total', $state * $quantity);
                                        }),
                                    TextInput::make('total')->label('Total')->disabled(),
                                ]),
                        ])
                        ->disableItemCreation(false) // Allow adding new items directly in the form
                        ->relationship('items') // Assuming 'items' relationship exists in RegisterArrival
                        ->createItemButtonLabel('Add Item')
                        ->afterStateHydrated(function ($state, $get, $set) {
                            $purchaseOrderId = $get('purchase_order_id'); // Accessing the state correctly

                            if ($purchaseOrderId) {
                                // Load the related PurchaseOrder
                                $purchaseOrder = PurchaseOrder::find($purchaseOrderId);

                                if ($purchaseOrder && $purchaseOrder->status === 'released') {
                                    // Get related items
                                    $items = PurchaseOrderItem::where('purchase_order_id', $purchaseOrderId)
                                        ->with('inventoryItem') // Eager load inventory items
                                        ->get();

                                    // Prepare the repeater data with the fetched items
                                    $itemsData = $items->map(function ($item) {
                                        return [
                                            'item_id' => $item->inventoryItem->id, // Assuming 'id' in InventoryItem
                                            'item_code' => $item->inventoryItem->item_code, // Assuming 'item_code' in InventoryItem
                                            'item_name' => $item->inventoryItem->name, // Assuming 'name' in InventoryItem
                                            'quantity' => $item->quantity,
                                            'price' => $item->price,
                                            'total' => $item->quantity * $item->price,
                                        ];
                                    })->toArray();

                                    // Update the state of the repeater field with the prepared items data
                                    $set('purchase_order_items', $itemsData); // This is the correct way to set the repeater data
                                } else {
                                    Notification::make()
                                        ->title('Invalid Purchase Order')
                                        ->danger()
                                        ->body('The selected purchase order is not in a released status.')
                                        ->send();
                                }
                            } else {
                                // Reset repeater if no purchase_order_id
                                $set('purchase_order_items', []);
                            }
                        }),

                ]),

            // Section 3: Arrival Information and Invoice Upload
            Section::make('Arrival Information and Invoice Upload')
                ->schema([
                    Grid::make(2) // Two-column layout for this section
                        ->schema([
                            Select::make('location_id')
                                ->label('Location')
                                ->relationship('location', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->options(function () {
                                    return InventoryLocation::orderByRaw("CASE WHEN location_type = 'arrival' THEN 1 ELSE 2 END")
                                                        ->pluck('name', 'id');
                                }),

                            DatePicker::make('received_date')
                                ->label('Received Date')
                                ->default(now()) // Default to today's date
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

        if ($purchaseOrder && $purchaseOrder->status === 'released') {
            // Populate the fields with the corresponding data
            $set('provider_name', $purchaseOrder->provider_name);
            $set('provider_phone', $purchaseOrder->provider_phone);
            $set('due_date', $purchaseOrder->wanted_date);

            // Get related items
            $items = PurchaseOrderItem::where('purchase_order_id', $purchaseOrderId)
                ->with('inventoryItem') // Eager load inventory items
                ->get();

            // Prepare the repeater data with the fetched items
            $itemsData = $items->map(function ($item) {
                return [
                    'item_id' => $item->inventoryItem->id, // Assuming 'id' in InventoryItem
                    'item_code' => $item->inventoryItem->item_code, // Assuming 'item_code' in InventoryItem
                    'item_name' => $item->inventoryItem->name, // Assuming 'name' in InventoryItem
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->quantity * $item->price,
                ];
            })->toArray();

            // Update the state of the repeater field with the prepared items data
            $set('purchase_order_items', $itemsData); // This is the correct way to set the repeater data
        } else {
            Notification::make()
                ->title('Invalid Purchase Order')
                ->danger()
                ->body('The selected purchase order is not in a released status.')
                ->send();

            // Reset fields if purchase order is not found or not in released status
            $set('provider_name', null);
            $set('provider_phone', null);
            $set('due_date', null);
            $set('purchase_order_items', []);
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