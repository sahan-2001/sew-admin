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
use Filament\Forms\Components\Hidden;
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
use Illuminate\Support\Facades\URL;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;

class CuttingRecordResource extends Resource
{
    protected static ?string $model = CuttingRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Cutting Department';
    protected static ?string $navigationLabel = 'Cutting Material Records';

    // Helper method to get line ID
    protected static function getLineId($orderType, $orderId, $itemName)
    {
        if ($orderType === 'customer_order') {
            $customerOrderDesc = \App\Models\CustomerOrderDescription::where('customer_order_id', $orderId)
                ->where('item_name', $itemName)
                ->first();
            return $customerOrderDesc ? $customerOrderDesc->id : '0';
        } elseif ($orderType === 'sample_order') {
            $sampleOrderItem = \App\Models\SampleOrderItem::where('sample_order_id', $orderId)
                ->where('item_name', $itemName)
                ->first();
            return $sampleOrderItem ? $sampleOrderItem->id : '0';
        }
        return '0';
    }

    // Helper method to get variation ID
    protected static function getVariationId($orderType, $lineId, $variationName)
    {
        if ($orderType === 'customer_order') {
            $variationItem = \App\Models\VariationItem::where('customer_order_description_id', $lineId)
                ->where('variation_name', $variationName)
                ->first();
            return $variationItem ? $variationItem->id : '0';
        } elseif ($orderType === 'sample_order') {
            $sampleVariation = \App\Models\SampleOrderVariation::where('sample_order_item_id', $lineId)
                ->where('variation_name', $variationName)
                ->first();
            return $sampleVariation ? $sampleVariation->id : '0';
        }
        return '0';
    }

    protected static function setReleaseMaterialItems($releaseMaterial, callable $set): void
    {
        // Filter out lines where remaining quantity is <= 0
        $validLines = $releaseMaterial->lines->filter(function ($line) {
            $cutQuantity = $line->cut_quantity ?? 0;
            return ($line->quantity - $cutQuantity) > 0;
        });

        if ($validLines->isEmpty()) {
            Notification::make()
                ->title('No Available Materials')
                ->body('All materials in this release have been fully used (remaining quantity = 0).')
                ->danger()
                ->persistent()
                ->send();
            
            $set('fetched_release_material_items', []);
            $set('release_material_id', null); // Clear invalid selection
            return;
        }

        $items = $validLines->map(function ($line) {
            $cutQuantity = $line->cut_quantity ?? 0;
            $remainingQuantity = number_format($line->quantity - $cutQuantity, 2, '.', '');
            
            return [
                'item_code' => $line->item->item_code ?? 'N/A',
                'item_name' => $line->item->name ?? 'N/A',
                'remaining_quantity' => $remainingQuantity,
                'uom' => $line->item->uom ?? 'N/A',
                'location' => $line->location->name ?? 'N/A',
                'release_material_line_id' => $line->id,
                'original_quantity' => $line->quantity, // Store original for validation
            ];
        })->toArray();

        $set('fetched_release_material_items', $items);
    }

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
                                            ->maxDate(now())
                                            ->disabled(function (string $context) {
                                                if (auth()->user()?->can('select_previous_performance_dates')) {
                                                    return false;
                                                }
                                                return $context !== 'create'; 
                                            }),
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
                                                $set('cutting_station_id', null);
                                                $set('release_material_id', null);
                                                $set('fetched_release_material_items', []);
                                                $set('fetched_order_items', []);
                                                $set('available_release_materials', []);
                                            })
                                            ->disabled(fn (string $context) => $context === 'edit'),

                                        Select::make('order_id')
                                            ->label('Order ID')
                                            ->required()
                                            ->searchable()
                                            ->helperText('You can not select orders with "planned", "Paused", "completed" status')
                                            ->reactive()
                                            ->disabled(fn (string $context) => $context === 'edit')
                                            ->options(function (callable $get) {
                                                $orderType = $get('order_type');

                                                if ($orderType === 'customer_order') {
                                                    return \App\Models\CustomerOrder::with('customer')
                                                        ->whereNotIn('status', ['planned', 'paused', 'completed'])
                                                        ->get()
                                                        ->mapWithKeys(function ($order) {
                                                            $customerName = $order->customer->name ?? 'Unknown Customer';
                                                            return [$order->order_id => "order ID - {$order->order_id} | Name - {$order->name} | Customer - {$customerName}"];
                                                        });
                                                } elseif ($orderType === 'sample_order') {
                                                    return \App\Models\SampleOrder::with('customer')
                                                        ->whereNotIn('status', ['planned', 'paused', 'completed'])
                                                        ->get()
                                                        ->mapWithKeys(function ($order) {
                                                            $customerName = $order->customer->name ?? 'Unknown Customer';
                                                            return [$order->order_id => "Order ID - {$order->order_id} | Name - {$order->name} | Customer - {$customerName}"];
                                                        });
                                                }

                                                return [];
                                            })


                                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                                // Clear all first
                                                $set('customer_id', null);
                                                $set('wanted_date', null);
                                                $set('cutting_station_name', null);
                                                $set('cutting_station_id', null);
                                                $set('release_material_id', null);
                                                $set('fetched_release_material_items', []);
                                                $set('fetched_order_items', []);
                                                $set('available_release_materials', []);

                                                if (!$state) return;

                                                $orderType = $get('order_type');

                                                $order = match ($orderType) {
                                                    'customer_order' => \App\Models\CustomerOrder::find($state),
                                                    'sample_order' => \App\Models\SampleOrder::find($state),
                                                    default => null,
                                                };

                                                if ($order) {
                                                    $set('customer_id', $order->customer_id ?? 'N/A');
                                                    $set('wanted_date', $order->wanted_delivery_date ?? 'N/A');
                                                }

                                                // Get all release materials with remaining quantity
                                                $releaseMaterials = \App\Models\ReleaseMaterial::with(['lines' => function($query) {
                                                    $query->where('quantity', '>', 0);
                                                }, 'lines.item', 'lines.location', 'cuttingStation'])
                                                    ->where('order_type', $orderType)
                                                    ->where('order_id', $state)
                                                    ->get();

                                                // Filter materials with remaining quantity
                                                $validReleaseMaterials = $releaseMaterials->filter(function ($rm) {
                                                    $totalRemaining = $rm->lines->sum(function ($line) {
                                                        $cutQuantity = $line->cut_quantity ?? 0;
                                                        return $line->quantity - $cutQuantity;
                                                    });
                                                    return $totalRemaining > 0;
                                                });

                                                if ($validReleaseMaterials->isEmpty()) {
                                                    Notification::make()
                                                        ->title('No Available Materials')
                                                        ->body('All released materials for this order have been fully used.')
                                                        ->danger()
                                                        ->persistent()
                                                        ->send();
                                                    return;
                                                }

                                                // Prepare options with remaining quantity info
                                                $releaseMaterialOptions = $validReleaseMaterials->mapWithKeys(function ($rm) {
                                                    $stationName = $rm->cuttingStation->name ?? 'Unknown Station';
                                                    $date = $rm->created_at->format('Y-m-d');
                                                    $remaining = $rm->lines->sum(function ($line) {
                                                        return $line->quantity - ($line->cut_quantity ?? 0);
                                                    });
                                                    return [$rm->id => "Cutting st. - {$stationName} | {$date} | (Remaining: {$remaining})"];
                                                })->toArray();

                                                $set('available_release_materials', $releaseMaterialOptions);

                                                // Auto-select if only one valid material
                                                if ($validReleaseMaterials->count() === 1) {
                                                    $singleRM = $validReleaseMaterials->first();
                                                    $set('release_material_id', $singleRM->id);
                                                    $set('cutting_station_name', $singleRM->cuttingStation->name ?? 'N/A');
                                                    $set('cutting_station_id', $singleRM->cuttingStation->id ?? null);
                                                    self::setReleaseMaterialItems($singleRM, $set);
                                                }
                                            
                                                // Fetch order items based on order type
                                                $orderItems = [];
                                                if ($orderType === 'customer_order') {
                                                    $customerOrderItems = \App\Models\CustomerOrderDescription::with('variationItems')
                                                        ->where('customer_order_id', $state)
                                                        ->get();

                                                    $orderItems = $customerOrderItems->map(function ($item) {
                                                        return [
                                                            'item_id' => $item->id,
                                                            'item_name' => $item->item_name,
                                                            'quantity' => $item->quantity,
                                                            'no_of_pieces' => 0,
                                                            'variations' => $item->variationItems->map(function ($variation) {
                                                                return [
                                                                    'var_item_id' => $variation->id,
                                                                    'var_item_name' => $variation->variation_name,
                                                                    'var_quantity' => $variation->quantity,
                                                                    'no_of_pieces_var' => 0,
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
                                                            'item_id' => $item->id,
                                                            'item_name' => $item->item_name,
                                                            'quantity' => $item->quantity,
                                                            'no_of_pieces' => 0,
                                                            'variations' => $item->variations->map(function ($variation) {
                                                                return [
                                                                    'var_item_id' => $variation->id,
                                                                    'var_item_name' => $variation->variation_name,
                                                                    'var_quantity' => $variation->quantity,
                                                                    'no_of_pieces_var' => 0,
                                                                ];
                                                            })->toArray()
                                                        ];
                                                    })->toArray();
                                                }

                                                $set('fetched_order_items', $orderItems);
                                            }),

                                        Select::make('release_material_id')
                                        ->label('Select Released Material Record')
                                        ->options(function (callable $get) {
                                            return $get('available_release_materials') ?? [];
                                        })
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                            if (!$state) {
                                                $set('fetched_release_material_items', []);
                                                return;
                                            }
                                            
                                            $releaseMaterial = \App\Models\ReleaseMaterial::with(['lines' => function($query) {
                                                $query->where('quantity', '>', 0);
                                            }, 'lines.item', 'lines.location', 'cuttingStation'])
                                                ->find($state);
                                            
                                            if ($releaseMaterial) {
                                                $set('cutting_station_name', $releaseMaterial->cuttingStation->name ?? 'N/A');
                                                $set('cutting_station_id', $releaseMaterial->cuttingStation->id ?? null);
                                                self::setReleaseMaterialItems($releaseMaterial, $set);
                                            }
                                        })
                                        ->required()
                                        ->disabled(function (callable $get) {
                                            return empty($get('available_release_materials'));
                                        }),
                                        
                                        Hidden::make('order_type'),
                                        Hidden::make('cutting_station_id'),
                                        Hidden::make('release_material_id'),

                                        TextInput::make('customer_id')
                                            ->label('Customer ID')
                                            ->disabled(),

                                        DatePicker::make('wanted_date')
                                            ->label('Wanted Delivery Date')
                                            ->disabled(),
                                        
                                        TextInput::make('cutting_station_name')
                                            ->label('Cutting Station')
                                            ->disabled(),

                                        Repeater::make('fetched_release_material_items')
                                            ->label('Existing Released Materials for the Cutting Station')
                                            ->schema([
                                                Grid::make(5)->schema([
                                                    TextInput::make('item_code')->label('Item Code')->disabled()->dehydrated(),
                                                    TextInput::make('item_name')->label('Item Name')->disabled(),
                                                    TextInput::make('remaining_quantity')->label('Quantity')->disabled(),
                                                    TextInput::make('uom')->label('UOM')->disabled(),
                                                    TextInput::make('location')->label('Location')->disabled(),
                                                    
                                                    TextInput::make('cut_quantity')
                                                        ->label('Cut Quantity')
                                                        ->numeric()
                                                        ->minValue(0)
                                                        ->required()
                                                        ->reactive()
                                                        ->afterStateUpdated(function ($state, $set, $get) {
                                                            $remaining = $get('remaining_quantity');
                                                            if ((float)$state > (float)$remaining) {
                                                                Notification::make()
                                                                    ->title('Invalid Quantity')
                                                                    ->body('Cannot cut more than remaining quantity')
                                                                    ->danger()
                                                                    ->send();
                                                                $set('cut_quantity', $remaining);
                                                            }
                                                        }),
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
                                            ->live()
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $from = $get('operated_time_from');
                                                $to = $get('operated_time_to');
                                                $now = now()->format('H:i');

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
                                            ->live()
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
                                        Repeater::make('fetched_order_items')
                                            ->label('Order Items')
                                            ->default([]) 
                                            ->schema([
                                                // Main Item
                                                Grid::make(3)->schema([
                                                    Hidden::make('item_type')
                                                        ->default(fn (callable $get) => $get('../../../../order_type') === 'customer_order' ? 'CO' : 'SO')
                                                        ->dehydrated(),
                                                    Hidden::make('item_id')
                                                        ->dehydrated(),
                                                    TextInput::make('item_name')
                                                        ->label('Item Name')
                                                        ->disabled(),
                                                    TextInput::make('quantity')
                                                        ->label('Quantity')
                                                        ->disabled()
                                                        ->visible(fn (callable $get) => empty($get('variations')))
                                                        ->dehydrated(),
                                                    TextInput::make('no_of_pieces')
                                                        ->label('Number of Pieces')
                                                        ->numeric()
                                                        ->required()
                                                        ->helperText('Be careful, your value may not have been entered correctly.')
                                                        ->visible(fn (callable $get) => empty($get('variations')))
                                                        ->live()
                                                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                            // Calculate grand total
                                                            $items = $get('../../fetched_order_items') ?? [];
                                                            $total = 0;
                                                            foreach ($items as $item) {
                                                                if (!empty($item['variations'])) {
                                                                    foreach ($item['variations'] as $variation) {
                                                                        $total += (int)($variation['no_of_pieces_var'] ?? 0);
                                                                    }
                                                                } else {
                                                                    $total += (int)($item['no_of_pieces'] ?? 0);
                                                                }
                                                            }
                                                            $set('../../grand_total_pieces', $total);

                                                            // Generate labels for main item
                                                            if (empty($get('variations')) && $state > 0) {
                                                                $orderType = $get('../../../../order_type');
                                                                $orderId = $get('../../../../order_id');
                                                                $itemName = $get('item_name');
                                                                
                                                                $lineId = static::getLineId($orderType, $orderId, $itemName);
                                                                $prefix = strtoupper(substr($orderType, 0, 1)) . 'O';
                                                                
                                                                $startLabel = sprintf('%s%s-%s-1', $prefix, $orderId, $lineId);
                                                                $endLabel = sprintf('%s%s-%s-%d', $prefix, $orderId, $lineId, $state);
                                                                
                                                                $set('start_label', $startLabel);
                                                                $set('end_label', $endLabel);
                                                            }
                                                        }),
                                                        
                                                    TextInput::make('total_variation_pieces')
                                                        ->label('Total Pieces of Variations')
                                                        ->disabled()
                                                        ->dehydrated(false)
                                                        ->numeric()
                                                        ->visible(fn (callable $get) => !empty($get('variations')))
                                                        ->live(),
                                                ]),
                                                
                                                // Nested Repeater for Variations
                                                Repeater::make('variations')
                                                    ->label('Variations')
                                                    ->schema([
                                                        Grid::make(4)->schema([
                                                            Hidden::make('variation_type')
                                                                ->default(fn (callable $get) => $get('../../../../../../order_type') === 'customer_order' ? 'CO' : 'SO')
                                                                ->dehydrated(),
                                                            Hidden::make('var_item_id')
                                                                ->dehydrated(),
                                                            TextInput::make('var_item_name')
                                                                ->label('Variation Name')
                                                                ->disabled(),
                                                            TextInput::make('var_quantity')
                                                                ->label('Quantity')
                                                                ->disabled()
                                                                ->dehydrated(),
                                                            TextInput::make('no_of_pieces_var')
                                                                ->label('Number of Pieces')
                                                                ->numeric()
                                                                ->required()
                                                                ->live()
                                                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                                    // Update variation total
                                                                    $variations = $get('../../variations') ?? [];
                                                                    $variationTotal = array_reduce($variations, fn ($carry, $item) => $carry + (int)($item['no_of_pieces_var'] ?? 0), 0);
                                                                    $set('../../total_variation_pieces', $variationTotal);

                                                                    // Update grand total
                                                                    $items = $get('../../../../fetched_order_items') ?? [];
                                                                    $grandTotal = 0;
                                                                    foreach ($items as $item) {
                                                                        if (!empty($item['variations'])) {
                                                                            foreach ($item['variations'] as $variation) {
                                                                                $grandTotal += (int)($variation['no_of_pieces_var'] ?? 0);
                                                                            }
                                                                        } else {
                                                                            $grandTotal += (int)($item['no_of_pieces'] ?? 0);
                                                                        }
                                                                    }
                                                                    $set('../../../../grand_total_pieces', $grandTotal);

                                                                    // Generate labels for variation
                                                                    if ($state > 0) {
                                                                        $orderType = $get('../../../../../../order_type');
                                                                        $orderId = $get('../../../../../../order_id');
                                                                        $parentItemName = $get('../../item_name');
                                                                        $variationName = $get('var_item_name');
                                                                        
                                                                        $lineId = static::getLineId($orderType, $orderId, $parentItemName);
                                                                        $variationId = static::getVariationId($orderType, $lineId, $variationName);
                                                                        $prefix = strtoupper(substr($orderType, 0, 1)) . 'O';
                                                                        
                                                                        $startLabel = sprintf('%s%s-%s-%s-1', $prefix, $orderId, $lineId, $variationId);
                                                                        $endLabel = sprintf('%s%s-%s-%s-%d', $prefix, $orderId, $lineId, $variationId, $state);
                                                                        
                                                                        $set('start_label_var', $startLabel);
                                                                        $set('end_label_var', $endLabel);
                                                                    }
                                                                }),

                                                            TextInput::make('start_label_var')
                                                                ->label('Start Label')
                                                                ->disabled()
                                                                ->dehydrated(),

                                                            TextInput::make('end_label_var')
                                                                ->label('End Label')
                                                                ->disabled()
                                                                ->dehydrated(),
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
                                        
                                        TextInput::make('grand_total_pieces')
                                            ->label('Grand Total of All Pieces')
                                            ->numeric()
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->live(),
                                    ]),
                            ]),

                        // Employees Tab
                        Tabs\Tab::make('Employees')
                            ->schema([
                                Section::make('Employee Data')
                                    ->schema([
                                        Group::make([
                                            Placeholder::make('pieces_display')
                                                ->label('Grand Total of Cut Pieces')
                                                ->content(fn (callable $get) => $get('grand_total_pieces') ?: 0)
                                                ->live(),
                                        ]),
                                        
                                        Repeater::make('employees')
                                            ->label('Cutting Employees')
                                            ->schema([
                                                Select::make('employee_id')
                                                    ->label('Employee')
                                                    ->required()
                                                    ->searchable()
                                                    ->options(function (callable $get, $state) {
                                                        $selectedUserIds = collect($get('../../employees'))
                                                            ->pluck('employee_id')
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
                                                    ->live()
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
                                                Placeholder::make('total_pieces_cut_display')
                                                    ->label('Total Cut Pieces')
                                                    ->content(function (callable $get, callable $set) {
                                                        $employees = $get('employees') ?? [];
                                                        $totalCut = collect($employees)->sum('pieces_cut');
                                                        $grandTotal = (int) ($get('grand_total_pieces') ?? 0);

                                                        if ($totalCut > $grandTotal && $grandTotal > 0) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Too many pieces assigned')
                                                                ->body("Total pieces cut ($totalCut) exceed the grand total ($grandTotal). All values will be reset.")
                                                                ->danger()
                                                                ->send();

                                                            $resetEmployees = collect($employees)->map(function ($emp) {
                                                                $emp['pieces_cut'] = 0;
                                                                return $emp;
                                                            })->toArray();

                                                            $set('employees', $resetEmployees);
                                                            return 0;
                                                        }

                                                        return $totalCut;
                                                    })
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
                                                    ->live(),

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
                                                    ->live(),

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
                                                Select::make('by_item_id')
                                                    ->label('By Product Item')
                                                    ->options(
                                                        \App\Models\InventoryItem::where('category', 'By Products')
                                                            ->pluck('name', 'id')
                                                    )
                                                    ->searchable()
                                                    ->live(),

                                                TextInput::make('by_amount')
                                                    ->label('Amount')
                                                    ->numeric()
                                                    ->required(fn (callable $get) => filled($get('inv_item_id'))),

                                                Select::make('by_unit')
                                                    ->label('Unit')
                                                    ->options([
                                                        'pcs' => 'Pieces',
                                                        'kgs' => 'Kilograms',
                                                        'liters' => 'Liters',
                                                        'meters' => 'Meters',
                                                    ])
                                                    ->required(fn (callable $get) => filled($get('inv_item_id'))),

                                                Select::make('by_location_id')
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

                                                TextInput::make('inspected_quantity')
                                                    ->label('Inspected Pieces')
                                                    ->numeric()
                                                    ->default(0),
                                                
                                                TextInput::make('accepted_quantity')
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
                    ->label('Order ID')
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                    
                Tables\Columns\TextColumn::make('total_pieces')
                    ->label('Pieces'),
                    
                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Operators')
                    ->counts('employees'),
                ...(
                Auth::user()->can('view audit columns')
                    ? [
                        TextColumn::make('created_by')->label('Created By')->toggleable()->sortable(),
                        TextColumn::make('updated_by')->label('Updated By')->toggleable()->sortable(),
                        TextColumn::make('created_at')->label('Created At')->toggleable()->dateTime()->sortable(),
                        TextColumn::make('updated_at')->label('Updated At')->toggleable()->dateTime()->sortable(),
                    ]
                    : []
                    ),
            ])
            ->filters([
                
            ])
            ->actions([
                Tables\Actions\Action::make('Print Report')
                    ->label('Print Report')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('cutting-records.print', ['cutting_record' => $record->id]))
                    ->openUrlInNewTab(),

                Action::make('Print Labels')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn ($record) => route('cutting-records.print-labels', $record))
                    ->openUrlInNewTab(),
                    
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
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
