<?php

namespace App\Filament\Resources\ReleaseMaterialResource\Pages;

use App\Filament\Resources\ReleaseMaterialResource;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use App\Models\Stock;
use App\Models\ReleaseMaterialLine;

class EditReleaseMaterial extends EditRecord
{
    protected static string $resource = ReleaseMaterialResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Section: Order Details
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
                                    ->reactive(),

                                Select::make('order_id')
                                    ->label('Order')
                                    ->required()
                                    ->options(function ($get) {
                                        $orderType = $get('order_type');
                                        if ($orderType === 'customer_order') {
                                            return CustomerOrder::pluck('name', 'order_id');
                                        } elseif ($orderType === 'sample_order') {
                                            return SampleOrder::pluck('name', 'order_id');
                                        }
                                        return [];
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $orderType = $get('order_type');
                                        if ($orderType === 'customer_order') {
                                            $order = CustomerOrder::with('customer')->find($state);
                                            if ($order) {
                                                $set('customer_id', $order->customer_id);
                                                $set('customer_name', $order->customer->name ?? 'Unknown');
                                                $set('wanted_date', $order->wanted_delivery_date);
                                            }
                                        } elseif ($orderType === 'sample_order') {
                                            $order = SampleOrder::with('customer')->find($state);
                                            if ($order) {
                                                $set('customer_id', $order->customer_id);
                                                $set('customer_name', $order->customer->name ?? 'Unknown');
                                                $set('wanted_date', $order->wanted_delivery_date);
                                            }
                                        } else {
                                            $set('customer_id', null);
                                            $set('customer_name', null);
                                            $set('wanted_date', null);
                                        }
                                    }),

                                TextInput::make('customer_id')
                                    ->label('Customer ID')
                                    ->disabled(),

                                TextInput::make('customer_name')
                                    ->label('Customer Name')
                                    ->disabled(),

                                TextInput::make('wanted_date')
                                    ->label('Wanted Date')
                                    ->disabled(),
                            ]),
                    ]),

                // Section: Production Line Details
                Section::make('Production Line Details')
                    ->schema([
                        Select::make('production_line_id')
                            ->label('Production Line')
                            ->relationship('productionLine', 'name')
                            ->required(),

                        Select::make('workstation_id')
                            ->label('Workstation')
                            ->relationship('workstation', 'name')
                            ->nullable(),
                    ]),

                // Section: Items
Section::make('Items')
    ->schema([
        Repeater::make('lines')
            ->relationship('lines') // Ensures data is saved to the 'lines' relationship
            ->schema([
                Grid::make(12)
                    ->schema([
                        // Item Selection
                        Select::make('item_id')
                            ->label('Item')
                            ->relationship('item', 'name') // Fetches item names from the InventoryItem model
                            ->required()
                            ->reactive()
                            ->columnSpan(3),

                        // Location Selection
                        Select::make('location_id')
                            ->label('Location')
                            ->options(function ($get) {
                                $itemId = $get('item_id');
                                if ($itemId) {
                                    return \App\Models\Stock::where('item_id', $itemId)
                                        ->with('location')
                                        ->get()
                                        ->mapWithKeys(function ($stock) {
                                            return [$stock->id => $stock->location->name ?? 'Unknown Location'];
                                        });
                                }
                                return [];
                            })
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $itemId = $get('item_id');
                                if ($state && $itemId) {
                                    $stock = \App\Models\Stock::where('item_id', $itemId)
                                        ->where('location_id', $state)
                                        ->first();
                                    if ($stock) {
                                        $set('stored_quantity', $stock->quantity);
                                        $set('cost', $stock->cost);
                                    } else {
                                        $set('stored_quantity', 0);
                                        $set('cost', 0);
                                    }
                                }
                            })
                            ->columnSpan(3),

                        // Stored Quantity
                        TextInput::make('stored_quantity')
                            ->label('Stored Quantity')
                            ->disabled()
                            ->columnSpan(2),

                        // Cost
                        TextInput::make('cost')
                            ->label('Cost')
                            ->disabled()
                            ->dehydrated(true)
                            ->columnSpan(2),

                        // Quantity
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $locationId = $get('location_id');
                                $itemId = $get('item_id');
                                if ($locationId && $itemId) {
                                    $stock = \App\Models\Stock::where('item_id', $itemId)
                                        ->where('location_id', $locationId)
                                        ->first();
                                    if ($stock && $state > $stock->quantity) {
                                        $set('quantity', $stock->quantity);
                                    }
                                }
                            })
                            ->columnSpan(2),
                    ]),
            ])
            ->columnSpan(12)
            ->createItemButtonLabel('Add Item'), // Button label for adding new items
    

                    ]),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $orderType = $data['order_type'] ?? null;
        $orderId = $data['order_id'] ?? null;

        if ($orderType === 'customer_order') {
            $order = CustomerOrder::with('customer')->find($orderId);
        } elseif ($orderType === 'sample_order') {
            $order = SampleOrder::with('customer')->find($orderId);
        }

        if (isset($order) && $order) {
            $data['customer_id'] = $order->customer_id;
            $data['customer_name'] = $order->customer->name ?? 'Unknown';
            $data['wanted_date'] = $order->wanted_delivery_date;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $currentLines = $this->record->lines;
        $originalLines = ReleaseMaterialLine::withTrashed()
            ->where('release_material_id', $this->record->id)
            ->get()
            ->keyBy(function ($line) {
                return $line->id ?? "{$line->item_id}-{$line->location_id}";
            });

        foreach ($currentLines as $line) {
            $key = $line->id ?? "{$line->item_id}-{$line->location_id}";

            $stock = Stock::where('item_id', $line->item_id)
                          ->where('location_id', $line->location_id)
                          ->first();

            $oldQty = $originalLines[$key]->quantity ?? 0;
            $newQty = $line->quantity;

            if ($stock) {
                $stock->quantity += $oldQty; // revert old quantity
                $stock->quantity -= $newQty; // subtract new quantity
                $stock->save();
            }
        }
    }
}