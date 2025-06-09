<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ReleaseMaterialResource\Pages;
use App\Models\ReleaseMaterial;
use App\Models\ReleaseMaterialLine;
use App\Models\InventoryItem;
use App\Models\Stock;
use App\Models\CuttingStation;
use Filament\Forms;
use Filament\Forms\Components\{Select, Textarea, Grid, Section, Repeater, TextInput};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\{TextColumn};
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;


class ReleaseMaterialResource extends Resource
{
    protected static ?string $model = ReleaseMaterial::class;
    protected static ?string $navigationIcon = 'heroicon-m-cube';
    protected static ?string $navigationGroup = 'Inventory Management'; 
    protected static ?string $navigationLabel = 'Release Materials';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            // Section 1: Order Details
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
                                ->reactive()
                                ->afterStateUpdated(function ($state, $set) {
                                    // Clear all order-related fields when order type changes
                                    $set('order_id', null);
                                    $set('customer_id', null);
                                    $set('customer_name', null);
                                    $set('wanted_date', null);
                                }),

                            Select::make('order_id')
                                ->label('Order')
                                ->required()
                                ->options(function ($get, $livewire) {
                                    $orderType = $get('order_type');
                                    $options = [];

                                    if ($orderType === 'customer_order') {
                                        $orders = \App\Models\CustomerOrder::with('customer')
                                            ->whereNotIn('status', ['planned', 'paused'])
                                            ->get();

                                        $options = $orders->pluck('name', 'order_id')->toArray();
                                    } elseif ($orderType === 'sample_order') {
                                        $orders = \App\Models\SampleOrder::with('customer')
                                            ->whereNotIn('status', ['planned', 'paused'])
                                            ->get();

                                        $options = $orders->pluck('name', 'order_id')->toArray();
                                    }

                                    return $options;
                                })
                                ->reactive()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $orderType = $get('order_type');
                                    if ($state === null) {
                                        $set('customer_id', null);
                                        $set('customer_name', null);
                                        $set('wanted_date', null);
                                        return;
                                    }
                                    
                                    if ($orderType === 'customer_order') {
                                        $order = \App\Models\CustomerOrder::with('customer')->find($state);
                                        if ($order) {
                                            $set('customer_id', $order->customer_id);
                                            $set('customer_name', $order->customer->name ?? 'Unknown');
                                            $set('wanted_date', $order->wanted_delivery_date);
                                        }
                                    } elseif ($orderType === 'sample_order') {
                                        $order = \App\Models\SampleOrder::with('customer')->find($state);
                                        if ($order) {
                                            $set('customer_id', $order->customer_id);
                                            $set('customer_name', $order->customer->name ?? 'Unknown');
                                            $set('wanted_date', $order->wanted_delivery_date);
                                        }
                                    }
                                }),

                            TextInput::make('customer_id')
                                ->label('Customer ID')
                                ->disabled()
                                ->hidden(fn ($get) => !$get('order_id')),

                            TextInput::make('customer_name')
                                ->label('Customer Name')
                                ->disabled()
                                ->hidden(fn ($get) => !$get('order_id')),

                            TextInput::make('wanted_date')
                                ->label('Wanted Date')
                                ->disabled()
                                ->hidden(fn ($get) => !$get('order_id')),

                            Textarea::make('notes')
                                ->label('Notes')
                                ->columnSpan(2)
                                ->nullable(),
                        ]),
                ]),

            // Section 2: Cutting Station Details
            Section::make('Cutting Station Details')
                ->schema([
                    Select::make('cutting_station_id')
                        ->label('Cutting Station')
                        ->options(fn () => \App\Models\CuttingStation::pluck('name', 'id'))
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $station = \App\Models\CuttingStation::find($state);
                            $set('cutting_station_description', $station?->description);
                        })
                        ->required(),

                    TextInput::make('cutting_station_description')
                        ->label('Description')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn ($get) => filled($get('cutting_station_id'))),
                    ]),

            // Section 3: Items
            Forms\Components\Section::make('Items')
                ->schema([
                    Forms\Components\Repeater::make('lines')
                        ->relationship('lines')
                        ->schema([
                            Forms\Components\Grid::make(12)
                                ->schema([
                                    Forms\Components\Select::make('item_id')
                                        ->label('Item')
                                        ->options(function () {
                                            return \App\Models\InventoryItem::all()
                                                ->mapWithKeys(function ($item) {
                                                    return [$item->id => "{$item->name} ({$item->item_code})"];
                                                });
                                        })
                                        ->required()
                                        ->reactive()
                                        ->columnSpan(3),

                                    Forms\Components\Select::make('stock_id')
                                        ->label('Select Stock (Location, Qty, Cost)')
                                        ->options(function ($get, $set, $component) {
                                            $itemId = $get('item_id');
                                            $currentStockId = $get('stock_id');
                                            $repeaterState = $get('../../lines'); // Get all repeater items
                                            
                                            // Get all selected stock IDs from other repeater items
                                            $usedStockIds = collect($repeaterState)
                                                ->pluck('stock_id')
                                                ->filter()
                                                ->unique()
                                                ->values()
                                                ->all();
                                                
                                            if ($itemId) {
                                                $query = \App\Models\Stock::where('item_id', $itemId)
                                                    ->where('quantity', '>', 0)
                                                    ->with('location');
                                                    
                                                // Exclude already selected stock IDs, but include the current one if set
                                                if (!empty($usedStockIds)) {
                                                    $query->whereNotIn('id', $usedStockIds)
                                                        ->orWhere('id', $currentStockId);
                                                }
                                                
                                                return $query->get()
                                                    ->mapWithKeys(function ($stock) {
                                                        $poId = $stock->purchase_order_id ?? '###'; 
                                                        return [
                                                            $stock->id => "{$stock->location->name} - Qty: {$stock->quantity} - PO ID: {$poId}"
                                                        ];
                                                    });
                                            }
                                            return [];
                                        })
                                        ->required()
                                        ->dehydrated(true)
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, $set) {
                                            $stock = \App\Models\Stock::with('location')->find($state);
                                            if ($stock) {
                                                $set('location_id', $stock->location_id);
                                                $set('stored_quantity', $stock->quantity);
                                                $set('cost', $stock->cost);
                                            } else {
                                                $set('location_id', null);
                                                $set('stored_quantity', 0);
                                                $set('cost', 0);
                                            }
                                        })
                                        ->columnSpan(4),

                                    Forms\Components\TextInput::make('stored_quantity')
                                        ->label('Available Quantity')
                                        ->disabled()
                                        ->reactive()
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('location_id')
                                        ->label('Location ID')
                                        ->disabled()
                                        ->reactive()
                                        ->dehydrated(true),
                                    
                                    Forms\Components\TextInput::make('cost')
                                        ->label('Cost')
                                        ->disabled()
                                        ->reactive()
                                        ->dehydrated(true)
                                        ->columnSpan(2),
                                    
                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Quantity')
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $stockId = $get('stock_id'); // Use stock_id if you're using the updated select method
                                            $stock = \App\Models\Stock::find($stockId);

                                            if ($stock && $state > $stock->quantity) {
                                                \Filament\Notifications\Notification::make()
                                                    ->title('Entered quantity exceeds available stock.')
                                                    ->body("Only {$stock->quantity} units are available at the selected location.")
                                                    ->danger()
                                                    ->persistent()
                                                    ->send();

                                                $set('quantity', null); // Clear the field
                                            }
                                        })
                                        ->numeric()
                                        ->minValue(1)
                                        ->columnSpan(2),
                                ]),
                        ])
                        ->minItems(1)
                        ->label('Item Lines')
                        ->required()
                        ->columnSpan(12),
                ]),
        ]);
    }


    
    

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('order_type')->sortable(),
                TextColumn::make('order_id')->sortable(),
                TextColumn::make('status'),
                TextColumn::make('created_at')->sortable(),
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
            ->actions([
            Tables\Actions\Action::make('viewItems')
                ->label('View Items')
                ->icon('heroicon-o-eye')
                ->modalHeading(fn ($record) => "Released Items with ID #" . str_pad($record->id, 5, '0', STR_PAD_LEFT))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->form([
                    Forms\Components\Repeater::make('items')
                        ->label('')
                        ->schema([
                            Forms\Components\Grid::make(4)
                                ->schema([
                                    Forms\Components\TextInput::make('item_code')
                                        ->label('Item Code')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('item_name')
                                        ->label('Item Name')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Quantity')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('location')
                                        ->label('Location')
                                        ->disabled(),
                                ])
                        ])
                        ->default(function ($record) {
                            return $record->lines->map(function ($line) {
                                return [
                                    'item_code' => $line->item->item_code ?? 'N/A',
                                    'item_name' => $line->item->name ?? 'N/A',
                                    'quantity' => $line->quantity,
                                    'location' => $line->location->name ?? 'N/A',
                                ];
                            });
                        })
                        ->disableItemCreation()
                        ->disableItemDeletion()
                        ->disableItemMovement()
                        ->columnSpan('full'),
                ]),
                
                Tables\Actions\Action::make('re-correction')
                    ->label('Re-correct')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'released')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        static::handleReCorrection($record);
                    })
                    ->icon('heroicon-o-trash'),
            ])
            ->recordUrl(null);
    }

    

    protected static function handleReCorrection($record)
    {
        \DB::transaction(function () use ($record) {
            $record->load('lines');
            
            foreach ($record->lines as $line) {
                $stock = Stock::find($line->stock_id);
                
                if ($stock) {
                    $stock->quantity += $line->quantity;
                    $stock->save();
                    
                    \Log::info("Stock restored - ID: {$stock->id}, Item: {$stock->item_id}, " . 
                            "Location: {$stock->location_id}, Added Qty: {$line->quantity}");
                } else {
                    \Log::warning("Stock not found for line ID: {$line->id}, Stock ID: {$line->stock_id}");
                }
                
                $line->delete();
            }
            
            $record->delete();
            
            \Log::info("Release Material and all lines soft deleted - ID: {$record->id}");
        });
        
        \Filament\Notifications\Notification::make()
            ->title('Re-correction completed successfully')
            ->success()
            ->send();
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReleaseMaterials::route('/'),
            'create' => Pages\CreateReleaseMaterial::route('/create'),
            'edit' => Pages\EditReleaseMaterial::route('/{record}/edit'),
        ];
    }
}