<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReleaseMaterialsResource\Pages;
use App\Models\ReleaseMaterials;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use App\Models\InventoryItem;
use App\Models\Warehouse;
use App\Models\Stock;
use App\Models\InventoryLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextColumn;

class ReleaseMaterialsResource extends Resource
{
    protected static ?string $model = ReleaseMaterials::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Order Information')
                ->schema([
                    Select::make('order_type')
                        ->label('Order Type')
                        ->options([
                            'customer_order' => 'Customer Order',
                            'sample_order' => 'Sample Order',
                        ])
                        ->reactive()
                        ->required()
                        ->afterStateUpdated(fn ($state, Set $set) => $set('order_id', null)),

                    Select::make('order_id')
                        ->label('Order')
                        ->options(fn (Get $get) => self::getOrderOptions($get))
                        ->searchable()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn ($state, Get $get, Set $set) => self::updateOrderDetails($state, $get, $set)),

                    TextInput::make('customer_name')->label('Customer Name')->disabled()->dehydrated(),
                    DatePicker::make('delivery_date')->label('Wanted Delivery Date')->disabled()->dehydrated(),
                ])->columns(2),

                Section::make('Release Items')
    ->schema([
        // Repeater for adding multiple release items
        // Repeater for adding multiple release items
Repeater::make('release_items')
->label('Release Items')
->schema([
    Grid::make(4) // 4-column structure for the fields inside the repeater
        ->schema([
            // Select Item by ID or Item Code
            Select::make('item_id')
                ->label('Select Item')
                ->options(function () {
                    return InventoryItem::query()
                        ->select('id', 'item_code', 'name')
                        ->get()
                        ->mapWithKeys(fn ($item) => [
                            $item->id => "{$item->item_code} - {$item->name}"
                        ])->toArray();
                })
                ->reactive()
                ->required()
                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                    // Reset location and quantity on item change
                    $set('location_id', null);
                    $set('quantity', null);
                    $set('quantity_stored', null);
                })
                ->helperText('Select an item from inventory')
                ->searchable()
                ->live(),

            // Select Location
            // Select Location
Select::make('location_id')
->label('Select Location')
->options(function (Get $get) {
    $itemId = $get('item_id');
    if (!$itemId) return [];

    return Stock::with(['location', 'location.warehouse'])
        ->where('item_id', $itemId)
        ->where('quantity', '>', 0) // Only show locations with available stock
        ->get()
        ->mapWithKeys(fn ($stock) => [
            $stock->location_id => "{$stock->location->warehouse->name} - {$stock->location->name}"
        ])->toArray();
})
->reactive()
->required()
->afterStateUpdated(function ($state, Get $get, Set $set) {
    $itemId = $get('item_id');
    $locationId = $state;

    if ($itemId && $locationId) {
        $stock = Stock::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->first();

        if ($stock) {
            $set('quantity_stored', $stock->quantity);
        }
    }
})
->disabled(fn (Get $get) => !$get('item_id'))
->searchable()
->live(),


            // Display quantity stored in the selected location
            TextInput::make('quantity_stored')
                ->label('Available Quantity')
                ->disabled()
                ->numeric(),

            // Enter quantity for release
            TextInput::make('quantity')
                ->label('Quantity to Release')
                ->numeric()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                    $quantityStored = $get('quantity_stored');
                    if ($state > $quantityStored) {
                        $set('error_quantity', 'Quantity exceeds available stock.');
                        $set('quantity', null); // Clear the entered quantity
                    } else {
                        $set('error_quantity', null);
                    }
                })
                ->minValue(1)
                ->helperText(fn (Get $get) => $get('error_quantity') ?? 'Enter the quantity to be released.')
                ->disabled(fn (Get $get) => !$get('location_id')),
        ]),
])
->columns(1), // Single column for the repeater


        ])
    ]);
    }
            

            private static function getOrderOptions(Get $get)
            {
                $type = $get('order_type');
                if (!$type) return [];
            
                return match ($type) {
                    'customer_order' => CustomerOrder::with('customer')->get()
                        ->mapWithKeys(fn ($order) => [$order->order_id => "#{$order->order_id} - {$order->name}"])
                        ->toArray(),
                    'sample_order' => SampleOrder::with('customer')->get()
                        ->mapWithKeys(fn ($order) => [$order->order_id => "#{$order->order_id} - {$order->name}"])
                        ->toArray(),
                    default => [],
                };
            }
            
            
    private static function getLocationOptions(Get $get)
    {
        $itemId = $get('item_id');
        if (!$itemId) return [];

        return InventoryLocation::query()
            ->whereHas('stocks', fn ($query) => $query->where('item_id', $itemId))
            ->pluck('name', 'id')
            ->toArray();
    }

    private static function updateOrderDetails($state, Get $get, Set $set)
    {
        $type = $get('order_type');
        if (!$type || !$state) return;

        $order = match ($type) {
            'customer_order' => CustomerOrder::with('customer')->find($state),
            'sample_order' => SampleOrder::with('customer')->find($state),
            default => null,
        };

        if ($order) {
            $set('customer_name', $order->customer->name);
            $set('delivery_date', $order->wanted_delivery_date);
        }
    }

    

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReleaseMaterials::route('/'),
            'create' => Pages\CreateReleaseMaterials::route('/create'),
            'edit' => Pages\EditReleaseMaterials::route('/{record}/edit'),
        ];
    }
}