<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CuttingRecordResource\Pages;
use App\Filament\Resources\CuttingRecordResource\RelationManagers;
use App\Models\CuttingRecord;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Tab;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Group;
use App\Models\CuttingStation;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\NonInventoryItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;



class CuttingRecordResource extends Resource
{
    protected static ?string $model = CuttingRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Cutting Performance')
                    ->columnSpanFull()
                    ->tabs([
                        // Order Tab
                        Tabs\Tab::make('Order')
                            ->schema([
                                Section::make('Cutting Station Details')
                                    ->columns(2)
                                    ->schema([
                                        DatePicker::make('operation_date')
                                            ->label('Operation Date')
                                            ->required()
                                            ->default(now())
                                            ->maxDate(now()),
                                    ]),
                                            
                                Section::make('Order Details')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('order_type')
                                            ->label('Order Type')
                                            ->required()
                                            ->options([
                                                'customer_order' => 'Customer Order',
                                                'sample_order' => 'Sample Order',
                                            ])
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $set) {
                                                // Clear all related fields
                                                $set('order_id', null);
                                                $set('customer_id', null);
                                                $set('wanted_date', null);
                                                $set('cutting_station_name', null);
                                                $set('fetched_release_material_items', []);
                                            }),

                                        Select::make('order_id')
                                            ->label('Order')
                                            ->required()
                                            ->searchable()
                                            ->reactive()
                                            ->options(function (callable $get) {
                                                $orderType = $get('order_type');

                                                if ($orderType === 'customer_order') {
                                                    return \App\Models\CustomerOrder::pluck('name', 'order_id');
                                                } elseif ($orderType === 'sample_order') {
                                                    return \App\Models\SampleOrder::pluck('name', 'order_id');
                                                }

                                                return [];
                                            })
                                            ->afterStateUpdated(function (callable $get, callable $set, $state, \Filament\Forms\Set $formSet) {
                                            // Clear all first
                                            $set('customer_id', null);
                                            $set('wanted_date', null);
                                            $set('cutting_station_name', null);
                                            $set('fetched_release_material_items', []);
        
                                            $orderType = $get('order_type');

                                            $order = match ($orderType) {
                                                'customer_order' => \App\Models\CustomerOrder::find($state),
                                                'sample_order' => \App\Models\SampleOrder::find($state),
                                                default => null,
                                            };

                                            $set('customer_id', $order->customer_id ?? 'N/A');
                                            $set('wanted_date', $order->wanted_delivery_date ?? 'N/A');

                                            $releaseMaterial = \App\Models\ReleaseMaterial::with('lines.item', 'lines.location')
                                                ->where('order_type', $orderType)
                                                ->where('order_id', $state)
                                                ->first();

                                            // Check if cutting_station_id exists
                                            if (!$releaseMaterial || !$releaseMaterial->cutting_station_id) {
                                                $set('customer_id', null);
                                                $set('wanted_date', null);
                                                $set('cutting_station_name', null);
                                                $set('fetched_release_material_items', []);

                                                \Filament\Notifications\Notification::make()
                                                    ->title('No Released Materials')
                                                    ->body('Materials were not released for any cutting station.')
                                                    ->danger()
                                                    ->persistent()
                                                    ->duration(5000)
                                                    ->send();

                                                return;
                                            }
                                            
                                            $set('cutting_station_name', $releaseMaterial->cuttingStation->name ?? 'N/A');

                                            // If cutting_station_id is present, show lines
                                            $items = $releaseMaterial->lines->map(function ($line) {
                                                return [
                                                    'item_code' => $line->item->item_code ?? 'N/A',
                                                    'item_name' => $line->item->name ?? 'N/A',
                                                    'quantity' => $line->quantity,
                                                    'uom' => $line->item->uom ?? 'N/A',
                                                    'location' => $line->location->name ?? 'N/A',
                                                ];
                                            })->toArray();

                                            $set('fetched_release_material_items', $items);
                                        
                                            // Fetch order items based on order type
                                            $orderItems = [];
                                            if ($orderType === 'customer_order') {
                                                $customerOrderItems = \App\Models\CustomerOrderDescription::with('variationItems')
                                                    ->where('customer_order_id', $state)
                                                    ->get();

                                                $orderItems = $customerOrderItems->map(function ($item) {
                                                    return [
                                                        'item_name' => $item->item_name,
                                                        'quantity' => $item->quantity,
                                                        'is_variation' => false,
                                                        'is_parent' => true,
                                                        'variations' => $item->variationItems->map(function ($variation) {
                                                            return [
                                                                'item_name' => $variation->variation_name,
                                                                'quantity' => $variation->quantity,
                                                                'is_variation' => true,
                                                                'is_parent' => false,
                                                            ];
                                                        })->toArray()
                                                    ];
                                                })->toArray();
                                            } elseif ($orderType === 'sample_order') {
                                                $sampleOrderItems = \App\Models\SampleOrderItem::with('variations')
                                                    ->where('sample_order_id', $state)
                                                    ->get();

                                                $orderItems = $sampleOrderItems->map(function ($item) {
                                                    return [
                                                        'item_name' => $item->item_name,
                                                        'quantity' => $item->quantity,
                                                        'is_variation' => false,
                                                        'is_parent' => true,
                                                        'variations' => $item->variations->map(function ($variation) {
                                                            return [
                                                                'item_name' => $variation->variation_name,
                                                                'quantity' => $variation->quantity,
                                                                'is_variation' => true,
                                                                'is_parent' => false,
                                                            ];
                                                        })->toArray()
                                                    ];
                                                })->toArray();
                                            }

                                            $set('fetched_order_items', $orderItems);
                                        }),

                                        TextInput::make('customer_id')
                                            ->label('Customer ID')
                                            ->disabled(),

                                        DatePicker::make('wanted_date')
                                            ->label('Wanted Delivery Date')
                                            ->disabled(),
                                        
                                        TextInput::make('cutting_station_name')
                                            ->label('Cutting Station')
                                            ->disabled(),

                                        Forms\Components\Repeater::make('fetched_release_material_items')
                                            ->label('Existing Released Materials for the Cutting Station')
                                            ->schema([
                                                Forms\Components\Grid::make(4)->schema([
                                                    Forms\Components\TextInput::make('item_code')->label('Item Code')->disabled(),
                                                    Forms\Components\TextInput::make('item_name')->label('Item Name')->disabled(),
                                                    Forms\Components\TextInput::make('quantity')->label('Quantity')->disabled(),
                                                    Forms\Components\TextInput::make('uom')->label('UOM')->disabled(),
                                                    Forms\Components\TextInput::make('location')->label('Location')->disabled(),
                                                ]),
                                            ])
                                            ->default([]) 
                                            ->disableItemCreation()
                                            ->disableItemDeletion()
                                            ->disableItemMovement()
                                            ->columnSpan('full'),
                                    ]),
                                    
                                Section::make('Operation Time')
                                    ->columns(3)
                                    ->schema([
                                        TimePicker::make('operated_time_from')
                                            ->label('From')
                                            ->required()
                                            ->withoutSeconds()
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $from = $get('operated_time_from');
                                                $to = $get('operated_time_to');
                                                $now = now()->format('H:i');

                                                if ($from && $from > $now) {
                                                    Notification::make()
                                                        ->title('Invalid time')
                                                        ->body('You cannot select a future time.')
                                                        ->danger()
                                                        ->send();
                                                    $set('operated_time_from', null);
                                                return;
                                                }

                                                if ($from && $to) {
                                                    $fromTime = \Carbon\Carbon::createFromFormat('H:i', $from);
                                                    $toTime = \Carbon\Carbon::createFromFormat('H:i', $to);

                                                    if ($toTime->lt($fromTime)) {
                                                        $toTime->addDay();
                                                        }

                                                        $minutes = $toTime->diffInMinutes($fromTime);
                                                        $hours = floor($minutes / 60);
                                                        $remainingMinutes = $minutes % 60;

                                                        $durationText = ($hours ? "{$hours}h " : '') . "{$remainingMinutes}m";
                                                        $set('operated_time_duration', trim($durationText));
                                                    } else {
                                                        $set('operated_time_duration', null);
                                                    }
                                                })
                                            ->columnSpan(1),

                                        TimePicker::make('operated_time_to')
                                            ->label('To')
                                            ->required()
                                            ->withoutSeconds()
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $from = $get('operated_time_from');
                                                $to = $get('operated_time_to');
                                                $now = now()->format('H:i');

                                                if ($to && $to > $now) {
                                                    Notification::make()
                                                        ->title('Invalid time')
                                                        ->body('You cannot select a future time.')
                                                        ->danger()
                                                        ->send();
                                                    $set('operated_time_to', null);
                                                return;
                                                }

                                                if ($from && $to) {
                                                    $fromTime = \Carbon\Carbon::createFromFormat('H:i', $from);
                                                    $toTime = \Carbon\Carbon::createFromFormat('H:i', $to);

                                                if ($toTime->lt($fromTime)) {
                                                    $toTime->addDay();
                                                }

                                                    $minutes = $toTime->diffInMinutes($fromTime);
                                                    $hours = floor($minutes / 60);
                                                    $remainingMinutes = $minutes % 60;

                                                    $durationText = ($hours ? "{$hours}h " : '') . "{$remainingMinutes}m";
                                                    $set('operated_time_duration', trim($durationText));
                                                } else {
                                                    $set('operated_time_duration', null);
                                                }
                                            })
                                            ->columnSpan(1),

                                        TextInput::make('operated_time_duration')
                                            ->label('Duration (hh:mm)')
                                            ->disabled()
                                            ->columnSpan(1),
                                    ]),
                            ]),
                            
                        // Cut Piece Label Tab
                        Tabs\Tab::make('Cut Piece Labels')
                            ->schema([         
                                Section::make('Order Items')
                                    ->schema([  
                                        Forms\Components\Repeater::make('fetched_order_items')
                                            ->label('Order Items')
                                            ->default([]) 
                                            ->schema([
                                                // Hidden field to store the unique key (generated once)
                                                Forms\Components\Hidden::make('unique_key')
                                                    ->default(fn () => substr(md5(uniqid(mt_rand(), true)), 0, 6))
                                                    ->dehydrated(),
                                                    
                                                // Main Item
                                                Forms\Components\Grid::make(4)->schema([
                                                    Forms\Components\TextInput::make('item_name')
                                                        ->label('Item Name')
                                                        ->disabled(),
                                                    Forms\Components\TextInput::make('quantity')
                                                        ->label('Quantity')
                                                        ->disabled()
                                                        ->visible(fn (callable $get) => empty($get('variations'))),
                                                    Forms\Components\TextInput::make('no_of_pieces')
                                                        ->label('Number of Pieces')
                                                        ->numeric()
                                                        ->required()
                                                        ->visible(fn (callable $get) => empty($get('variations')))
                                                        ->reactive()
                                                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                            $items = $get('../../fetched_order_items') ?? [];
                                                            $total = 0;
                                                            foreach ($items as $item) {
                                                                if (!empty($item['variations'])) {
                                                                    $total += (int)($item['total_variation_pieces'] ?? 0);
                                                                } else {
                                                                    $total += (int)($item['no_of_pieces'] ?? 0);
                                                                }
                                                            }
                                                            $set('../../grand_total_pieces', $total);

                                                            // Generate labels for main item if no variations
                                                            if (empty($get('variations'))) {
                                                                $orderType = $get('../../../../order_type');
                                                                $orderId = $get('../../../../order_id');
                                                                
                                                                // Get the order line ID based on order type
                                                                $lineId = '';
                                                                if ($orderType === 'customer_order') {
                                                                    $customerOrderDesc = \App\Models\CustomerOrderDescription::where('customer_order_id', $orderId)
                                                                        ->where('item_name', $get('item_name'))
                                                                        ->first();
                                                                    $lineId = $customerOrderDesc ? $customerOrderDesc->id : '0';
                                                                } elseif ($orderType === 'sample_order') {
                                                                    $sampleOrderItem = \App\Models\SampleOrderItem::where('sample_order_id', $orderId)
                                                                        ->where('item_name', $get('item_name'))
                                                                        ->first();
                                                                    $lineId = $sampleOrderItem ? $sampleOrderItem->id : '0';
                                                                }
                                                                
                                                                // Generate labels
                                                                $prefix = strtoupper(substr($orderType, 0, 1)) . 'O';
                                                                $labels = [];
                                                                for ($i = 1; $i <= $state; $i++) {
                                                                    $labels[] = sprintf('%s%s-%s-%d', $prefix, $orderId, $lineId, $i);
                                                                }
                                                                
                                                                $set('start_label', $labels[0] ?? '');
                                                                if (count($labels) > 1) {
                                                                    $set('end_label', end($labels));
                                                                } else {
                                                                    $set('end_label', $labels[0] ?? '');
                                                                }
                                                            }
                                                        }),
                                                        
                                                    Forms\Components\TextInput::make('total_variation_pieces')
                                                        ->label('Total Pieces of Variations')
                                                        ->disabled()
                                                        ->dehydrated(false)
                                                        ->numeric()
                                                        ->visible(fn (callable $get) => !empty($get('variations')))
                                                        ->reactive()
                                                        ->afterStateHydrated(function (callable $get, callable $set) {
                                                            $variations = $get('variations') ?? [];
                                                            $total = array_reduce($variations, fn ($carry, $item) => $carry + (int)($item['no_of_pieces_var'] ?? 0), 0);
                                                            $set('total_variation_pieces', $total);
                                                        })
                                                        ->extraInputAttributes(['style' => 'font-weight: bold;']),

                                                    TextInput::make('start_label')
                                                        ->label('Start Label')
                                                        ->formatStateUsing(function ($state, callable $get, callable $set) {
                                                            // Generate full format when displaying
                                                            $orderType = $get('../../../../order_type');
                                                            $orderId = $get('../../../../order_id');
                                                            $itemName = $get('item_name');
                                                            
                                                            if (empty($get('variations'))) {
                                                                $lineId = $this->getLineId($orderType, $orderId, $itemName);
                                                                return sprintf('%s%s-%s-%d',
                                                                    strtoupper(substr($orderType, 0, 1)) . 'O',
                                                                    $orderId,
                                                                    $lineId,
                                                                    1 // Starting number
                                                                );
                                                            }
                                                            return $state;
                                                        })
                                                        ->disabled()
                                                        ->dehydrated()
                                                        ->visible(fn (callable $get) => empty($get('variations'))),

                                                    TextInput::make('end_label')
                                                        ->label('End Label')
                                                        ->formatStateUsing(function ($state, callable $get, callable $set) {
                                                            // Generate full format when displaying
                                                            $orderType = $get('../../../../order_type');
                                                            $orderId = $get('../../../../order_id');
                                                            $itemName = $get('item_name');
                                                            $pieces = (int) $get('no_of_pieces');
                                                            
                                                            if (empty($get('variations')) && $pieces > 0) {
                                                                $lineId = $this->getLineId($orderType, $orderId, $itemName);
                                                                return sprintf('%s%s-%s-%d',
                                                                    strtoupper(substr($orderType, 0, 1)) . 'O',
                                                                    $orderId,
                                                                    $lineId,
                                                                    $pieces
                                                                );
                                                            }
                                                            return $state;
                                                        })
                                                        ->disabled()
                                                        ->dehydrated()
                                                        ->visible(fn (callable $get) => empty($get('variations'))),
                                                    ]),
                                                
                                                // Nested Repeater for Variations
                                                Forms\Components\Repeater::make('variations')
                                                    ->label('Variations')
                                                    ->schema([
                                                        Forms\Components\Grid::make(4)->schema([
                                                            Forms\Components\TextInput::make('item_name')
                                                                ->label('Variation Name')
                                                                ->disabled(),
                                                            Forms\Components\TextInput::make('quantity')
                                                                ->label('Quantity')
                                                                ->disabled(),
                                                            TextInput::make('no_of_pieces_var')
                                                                ->label('Number of Pieces')
                                                                ->numeric()
                                                                ->required()
                                                                ->reactive()
                                                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                                    $variations = $get('../../variations') ?? [];
                                                                    $variationTotal = array_reduce($variations, fn ($carry, $item) => $carry + (int)($item['no_of_pieces_var'] ?? 0), 0);
                                                                    $set('../../total_variation_pieces', $variationTotal);

                                                                    $items = $get('../../../../fetched_order_items') ?? [];
                                                                    $grandTotal = 0;
                                                                    foreach ($items as $item) {
                                                                        if (!empty($item['variations'])) {
                                                                            $grandTotal += (int)($item['total_variation_pieces'] ?? 0);
                                                                        } else {
                                                                            $grandTotal += (int)($item['no_of_pieces'] ?? 0);
                                                                        }
                                                                    }
                                                                    $set('../../../../grand_total_pieces', $grandTotal);

                                                                    // Generate labels for variations
                                                                    $orderType = $get('../../../../../../order_type');
                                                                    $orderId = $get('../../../../../../order_id');
                                                                    
                                                                    // Get the order line ID based on order type
                                                                    $lineId = '';
                                                                    if ($orderType === 'customer_order') {
                                                                        $customerOrderDesc = \App\Models\CustomerOrderDescription::where('customer_order_id', $orderId)
                                                                            ->where('item_name', $get('../../item_name'))
                                                                            ->first();
                                                                        $lineId = $customerOrderDesc ? $customerOrderDesc->id : '0';
                                                                    } elseif ($orderType === 'sample_order') {
                                                                        $sampleOrderItem = \App\Models\SampleOrderItem::where('sample_order_id', $orderId)
                                                                            ->where('item_name', $get('../../item_name'))
                                                                            ->first();
                                                                        $lineId = $sampleOrderItem ? $sampleOrderItem->id : '0';
                                                                    }
                                                                    
                                                                    // Get variation ID
                                                                    $variationId = '';
                                                                    if ($orderType === 'customer_order') {
                                                                        $variationItem = \App\Models\VariationItem::where('customer_order_description_id', $lineId)
                                                                            ->where('variation_name', $get('item_name'))
                                                                            ->first();
                                                                        $variationId = $variationItem ? $variationItem->id : '0';
                                                                    } elseif ($orderType === 'sample_order') {
                                                                        $sampleVariation = \App\Models\SampleOrderVariation::where('sample_order_item_id', $lineId)
                                                                            ->where('variation_name', $get('item_name'))
                                                                            ->first();
                                                                        $variationId = $sampleVariation ? $sampleVariation->id : '0';
                                                                    }
                                                                    
                                                                    // Generate labels
                                                                    $prefix = strtoupper(substr($orderType, 0, 1)) . 'O';
                                                                    $labels = [];
                                                                    for ($i = 1; $i <= $state; $i++) {
                                                                        $labels[] = sprintf('%s%s-%s-%s-%d', $prefix, $orderId, $lineId, $variationId, $i);
                                                                    }
                                                                    
                                                                    $set('start_label_var', $labels[0] ?? '');
                                                                    if (count($labels) > 1) {
                                                                        $set('end_label_var', end($labels));
                                                                    } else {
                                                                        $set('end_label_var', $labels[0] ?? '');
                                                                    }
                                                                }),

                                                            TextInput::make('start_label_var')
                                                                ->label('Start Label')
                                                                ->formatStateUsing(function ($state, callable $get, callable $set) {
                                                                    $orderType = $get('../../../../../../order_type');
                                                                    $orderId = $get('../../../../../../order_id');
                                                                    $parentItemName = $get('../../item_name');
                                                                    $variationName = $get('item_name');
                                                                    
                                                                    $lineId = $this->getLineId($orderType, $orderId, $parentItemName);
                                                                    $variationId = $this->getVariationId($orderType, $lineId, $variationName);
                                                                    
                                                                    return sprintf('%s%s-%s-%s-%d',
                                                                        strtoupper(substr($orderType, 0, 1)) . 'O',
                                                                        $orderId,
                                                                        $lineId,
                                                                        $variationId,
                                                                        1 // Starting number
                                                                    );
                                                                })
                                                                ->disabled()
                                                                ->dehydrated()
                                                                ->visible(fn (callable $get) => !empty($get('variations'))),

                                                            TextInput::make('end_label_var')
                                                                ->label('End Label')
                                                                ->formatStateUsing(function ($state, callable $get, callable $set) {
                                                                    $orderType = $get('../../../../../../order_type');
                                                                    $orderId = $get('../../../../../../order_id');
                                                                    $parentItemName = $get('../../item_name');
                                                                    $variationName = $get('item_name');
                                                                    $pieces = (int) $get('no_of_pieces_var');
                                                                    
                                                                    $lineId = $this->getLineId($orderType, $orderId, $parentItemName);
                                                                    $variationId = $this->getVariationId($orderType, $lineId, $variationName);
                                                                    
                                                                    return sprintf('%s%s-%s-%s-%d',
                                                                        strtoupper(substr($orderType, 0, 1)) . 'O',
                                                                        $orderId,
                                                                        $lineId,
                                                                        $variationId,
                                                                        $pieces
                                                                    );
                                                                })
                                                                ->disabled()
                                                                ->dehydrated()
                                                                ->visible(fn (callable $get) => !empty($get('variations'))),
                                                        ]),
                                                    ])
                                                    ->default([])
                                                    ->disableItemCreation()
                                                    ->disableItemDeletion()
                                                    ->disableItemMovement()
                                                    ->columnSpan('full')
                                                    ->visible(fn (callable $get): bool => !empty($get('variations'))),
                                            ])
                                            ->default([])
                                            ->disableItemCreation()
                                            ->disableItemDeletion()
                                            ->disableItemMovement()
                                            ->columnSpan('full')
                                            ->visible(fn (callable $get): bool => !empty($get('fetched_order_items'))),
                                        
                                        Forms\Components\TextInput::make('grand_total_pieces')
                                            ->label('Grand Total of All Pieces')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->reactive()
                                            ->afterStateHydrated(function (callable $get, callable $set) {
                                                $items = $get('fetched_order_items') ?? [];
                                                $total = 0;
                                                foreach ($items as $item) {
                                                    if (!empty($item['variations'])) {
                                                        $total += (int)($item['total_variation_pieces'] ?? 0);
                                                    } else {
                                                        $total += (int)($item['no_of_pieces'] ?? 0);
                                                    }
                                                }
                                                $set('grand_total_pieces', $total);
                                            }),
                                    ]),
                            ]),

                            // Employees Tab
                            Tabs\Tab::make('Employees')
                                ->schema([
                                    Section::make('Employee Data')
                                        ->schema([
                                            // Moved the Label Summary inside this section
                                            Group::make([
                                                Placeholder::make('pieces')
                                                    ->label('Grand Total of Cut Pieces')
                                                    ->content(fn (callable $get) => $get('grand_total_pieces') ?: 0)
                                                    ->reactive(),
                                            ]),
                                            
                                            Repeater::make('employees')
                                                ->relationship()
                                                ->label('Cutting Employees')
                                                ->schema([
                                                    Select::make('employee_id')
                                                        ->label('Employee')
                                                        ->required()
                                                        ->searchable()
                                                        ->options(function (callable $get, $state) {
                                                            $selectedUserIds = collect($get('../../employees'))
                                                                ->pluck('user_id')
                                                                ->filter()
                                                                ->reject(fn($id) => $id === $state)
                                                                ->unique();

                                                            return \App\Models\User::role('employee')
                                                                ->whereNotIn('id', $selectedUserIds)
                                                                ->pluck('name', 'id');
                                                        }),
                                                        
                                                    TextInput::make('pieces_cut')
                                                        ->label('Pieces Cut')
                                                        ->numeric()
                                                        ->required()
                                                        ->reactive()
                                                        ->default(0),
                                                        
                                                    Select::make('supervisor_id')
                                                        ->label('Supervisor')
                                                        ->searchable()
                                                        ->options(
                                                            \App\Models\User::role('supervisor')->pluck('name', 'id')
                                                        ),
                                                        
                                                    Textarea::make('notes')
                                                        ->label('Notes')
                                                        ->columnSpanFull(),
                                                ])
                                                ->columns(3)
                                                ->columnSpanFull(),

                                            Section::make('Summary')
                                            ->schema([
                                                Placeholder::make('total_pieces_cut')
                                                    ->label('Total Cut Pieces')
                                                    ->content(function (callable $get, callable $set) {
                                                        $employees = $get('employees') ?? [];

                                                        $totalCut = collect($employees)->sum(function ($item) {
                                                            return (int) ($item['pieces_cut'] ?? 0);
                                                        });

                                                        $set('total_pieces_cut', $totalCut);

                                                        // Perform validation logic
                                                        $grandTotal = (int) ($get('grand_total_pieces') ?? 0);

                                                        if ($totalCut > $grandTotal) {
                                                            $set('employees', []);

                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Too many pieces assigned')
                                                                ->body("Total pieces cut ($totalCut) exceed the grand total ($grandTotal). Employee entries have been cleared.")
                                                                ->danger()
                                                                ->duration(5000)
                                                                ->persistent()
                                                                ->send();
                                                        }

                                                        return $totalCut;
                                                    })
                                                    ->reactive()
                                                    ->live(),
                                            ]),
                                    ]),
                                ]),                                                   
                            
                        // Waste Tab
                        Tabs\Tab::make('Waste')
                            ->schema([
                                Section::make('Material Waste')
                                    ->schema([
                                        Repeater::make('waste_records')
                                            ->label('Inventory Waste')
                                            ->schema([
                                                Select::make('inv_item_id')
                                                    ->label('Waste Item')
                                                    ->options(InventoryItem::where('category', 'Waste Item')->pluck('name', 'id'))
                                                    ->searchable()
                                                    ->reactive(),

                                                TextInput::make('inv_amount')
                                                    ->label('Amount')
                                                    ->numeric()
                                                    ->required(fn (callable $get) => filled($get('inv_item_id'))),
                                                    
                                                Select::make('inv_unit')
                                                    ->label('Unit')
                                                    ->options([
                                                        'pcs' => 'Pieces',
                                                        'kgs' => 'Kilograms',
                                                        'liters' => 'Liters',
                                                        'meters' => 'Meters',
                                                    ])
                                                    ->required(fn (callable $get) => filled($get('inv_item_idd'))),
                                                    
                                                Select::make('inv_location_id')
                                                    ->label('Location')
                                                    ->options(InventoryLocation::where('location_type', 'picking')->pluck('name', 'id'))
                                                    ->searchable()
                                                    ->required(fn (callable $get) => filled($get('inv_item_id'))),
                                            ])
                                            ->columns(4),
                                            
                                        Repeater::make('non_inventory_waste')
                                            ->label('Non-Inventory Waste')
                                            ->schema([
                                                Select::make('non_i_item_id')
                                                    ->label('Item')
                                                    ->options(NonInventoryItem::pluck('name', 'id'))
                                                    ->searchable()
                                                    ->reactive(),

                                                TextInput::make('non_i_amount')
                                                    ->label('Amount')
                                                    ->numeric()
                                                    ->required(fn (callable $get) => filled($get('non_i_item_id'))),
                                                    
                                                Select::make('non_i_unit')
                                                    ->label('Unit')
                                                    ->options([
                                                        'minutes' => 'Minutes',
                                                        'hours' => 'Hours',
                                                    ])
                                                    ->required(fn (callable $get) => filled($get('non_i_item_id'))),
                                            ])
                                            ->columns(3),

                                        Repeater::make('by_product_records')
                                            ->label('By Products')
                                            ->schema([
                                                Select::make('inv_item_id')
                                                    ->label('By Product Item')
                                                    ->options(
                                                        \App\Models\InventoryItem::where('category', 'By Products')
                                                            ->pluck('name', 'id')
                                                    )
                                                    ->searchable()
                                                    ->reactive(),

                                                TextInput::make('inv_amount')
                                                    ->label('Amount')
                                                    ->numeric()
                                                    ->required(fn (callable $get) => filled($get('inv_item_id'))),

                                                Select::make('inv_unit')
                                                    ->label('Unit')
                                                    ->options([
                                                        'pcs' => 'Pieces',
                                                        'kgs' => 'Kilograms',
                                                        'liters' => 'Liters',
                                                        'meters' => 'Meters',
                                                    ])
                                                    ->required(fn (callable $get) => filled($get('inv_item_id'))),

                                                Select::make('inv_location_id')
                                                    ->label('Location')
                                                    ->options(
                                                        \App\Models\InventoryLocation::where('location_type', 'picking')
                                                            ->pluck('name', 'id')
                                                    )
                                                    ->searchable()
                                                    ->required(fn (callable $get) => filled($get('inv_item_id'))),
                                            ])
                                            ->columns(4)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                            
                        // Quality Control Tab
                        Tabs\Tab::make('Quality Control')
                            ->schema([
                                Section::make('Quality Inspection')
                                    ->schema([
                                        Repeater::make('qualityControls') 
                                            ->relationship()
                                            ->schema([
                                                Select::make('qc_user_id')
                                                    ->label('Quality Control Officer')
                                                    ->required()
                                                    ->searchable()
                                                    ->options(function (callable $get, $state) {
                                                        $selectedUserIds = collect($get('../../qualityControls'))
                                                            ->pluck('qc_user_id')
                                                            ->filter()
                                                            ->reject(fn ($id) => $id === $state) 
                                                            ->unique();

                                                        return \App\Models\User::role('quality control')
                                                            ->whereNotIn('id', $selectedUserIds)
                                                            ->pluck('name', 'id');
                                                    }),


                                                TextInput::make('inspected_pieces')
                                                    ->label('Inspected Pieces')
                                                    ->numeric()
                                                    ->default(0),
                                                
                                                TextInput::make('accepted_pieces')
                                                    ->label('Accepted Pieces')
                                                    ->numeric()
                                                    ->default(0),

                                                Textarea::make('notes')
                                                    ->label('Notes')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull(),
                                        ]),
                            ]),
                    ]),
            ]);
    }




    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('operation_date')
                    ->label('Date')
                    ->date(),
                    
                Tables\Columns\TextColumn::make('cuttingStation.name')
                    ->label('Station'),
                    
                Tables\Columns\TextColumn::make('order_type')
                    ->label('Order Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'customer_order' => 'Customer',
                        'sample_order' => 'Sample',
                        'internal' => 'Internal',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order ID'),
                    
                Tables\Columns\TextColumn::make('total_pieces')
                    ->label('Pieces'),
                    
                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Operators')
                    ->counts('employees'),
            ])
            ->filters([
                
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
            'index' => Pages\ListCuttingRecords::route('/'),
            'create' => Pages\CreateCuttingRecord::route('/create'),
            'edit' => Pages\EditCuttingRecord::route('/{record}/edit'),
        ];
    }
}
