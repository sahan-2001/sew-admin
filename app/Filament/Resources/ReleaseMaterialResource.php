<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ReleaseMaterialResource\Pages;
use App\Models\ReleaseMaterial;
use App\Models\ReleaseMaterialLine;
use App\Models\InventoryItem;
use App\Models\Stock;
use App\Models\ProductionLine;
use App\Models\Workstation;
use Filament\Forms;
use Filament\Forms\Components\{Select, Textarea, Grid, Section, Repeater, TextInput};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\{TextColumn};
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

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
                                ->reactive(),

                            Select::make('order_id')
                                ->label('Order')
                                ->required()
                                ->options(function ($get) {
                                    $orderType = $get('order_type');
                                    if ($orderType === 'customer_order') {
                                        return \App\Models\CustomerOrder::pluck('name', 'order_id');
                                    } elseif ($orderType === 'sample_order') {
                                        return \App\Models\SampleOrder::pluck('name', 'order_id');
                                    }
                                    return [];
                                })
                                ->reactive()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $orderType = $get('order_type');
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
                                    } else {
                                        $set('customer_id', null);
                                        $set('customer_name', null);
                                        $set('wanted_date', null);
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
                                ->nullable(),
                        ]),
                ]),

            // Section 2: Production Line Details
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

                                    Forms\Components\Select::make('location_id')
    ->label('Stored Location')
    ->options(function ($get) {
        $itemId = $get('item_id');
        if ($itemId) {
            return Stock::where('item_id', $itemId)
                ->with('location')
                ->get()
                ->mapWithKeys(function ($stock) {
                    return [$stock->location_id => $stock->location->name ?? 'Unknown Location'];
                });
        }
        return [];
    })
    ->required()
    ->reactive()
    ->afterStateUpdated(function ($state, $set, $get) {
        $itemId = $get('item_id');
        if ($state && $itemId) {
            $stock = Stock::where('item_id', $itemId)
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

                                    Forms\Components\TextInput::make('stored_quantity')
                                        ->label('Available Quantity')
                                        ->disabled()
                                        ->reactive()
                                        ->columnSpan(2),

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
                                            $locationId = $get('location_id');
                                            $itemId = $get('item_id');
                                            if ($locationId && $itemId) {
                                                $stock = Stock::where('item_id', $itemId)
                                                    ->where('location_id', $locationId)
                                                    ->first();
                                                if ($stock && $state > $stock->quantity) {
                                                    $set('quantity', $stock->quantity); 
                                                }
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
                TextColumn::make('production_line_id')->sortable(),
                TextColumn::make('workstation_id')->sortable(),
                TextColumn::make('created_at')->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('re-correction')
                    ->label('Re-correct')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        // Perform the re-correction logic
                        static::handleReCorrection($record);
                    })
                    ->icon('heroicon-o-trash'),
            ])
            ->recordUrl(null);
    }

    

    protected static function handleReCorrection($record)
    {
        // Soft delete the ReleaseMaterial record
        $record->delete();

        // Retrieve related lines
        $lines = $record->lines;

        foreach ($lines as $line) {
            // Soft delete the ReleaseMaterialLine record
            $line->delete();

            // Update the stock table
            $stock = \App\Models\Stock::where('item_id', $line->item_id)
                ->where('location_id', $line->location_id)
                ->first();

            if ($stock) {
                $stock->quantity += $line->quantity;
                $stock->save();
            }
        }
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