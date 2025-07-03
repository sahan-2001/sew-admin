<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockOverviewResource\Pages;
use App\Models\Stock;
use App\Models\EmergencyStock;
use App\Models\InventoryItem;
use App\Models\Warehouse;
use App\Models\InventoryLocation;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class StockOverviewResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationLabel = 'Stocks';
    protected static ?string $navigationGroup = 'Inventory Management'; 


    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User & Timestamps')
                    ->schema([
                        Forms\Components\TextInput::make('user_id')
                            ->label('User ID')
                            ->default(fn () => auth()->id())
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('user_email')
                            ->label('User Email')
                            ->default(fn () => auth()->user()?->email)
                            ->disabled(),

                        Forms\Components\DatePicker::make('received_date')
                            ->label('Received Date')
                            ->default(now())
                            ->dehydrated()
                            ->required(),

                        Forms\Components\DatePicker::make('updated_date')
                            ->label('Updated Date')
                            ->default(today())
                            ->dehydrated()
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Stock Details')
                    ->schema([
                        Forms\Components\Select::make('item_id')
                            ->label('Item')
                            ->options(
                                \App\Models\InventoryItem::whereNull('deleted_at')
                                    ->get()
                                    ->mapWithKeys(fn ($item) => [
                                        $item->id => "{$item->item_code} - {$item->name}"
                                    ])
                            )
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->dehydrated()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('available_quantity', null);
                                $set('quantity', null);
                                $set('location_id', null);

                                if ($state) {
                                    $set('available_quantity', \App\Models\InventoryItem::find($state)?->available_quantity);
                                }
                            }),


                        Forms\Components\TextInput::make('available_quantity')
                            ->label('Available Quantity')
                            ->disabled()
                            ->dehydrated(false), 

                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->dehydrated()
                            ->required(),
                        
                        Forms\Components\TextInput::make('cost')
                            ->label('Cost')
                            ->numeric()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\Select::make('location_id')
                            ->label('Location (Picking)')
                            ->options(
                                \App\Models\InventoryLocation::where('location_type', 'picking')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->dehydrated()
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->query(Stock::query()->where('quantity', '>', 0)) 
            ->columns([
                TextColumn::make('location.warehouse.name')
                    ->label('Warehouse')
                    ->sortable(),

                TextColumn::make('location.name')
                    ->label('Location')
                    ->sortable(),

                TextColumn::make('item.item_code')
                    ->label('Item Code')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('item.name')
                    ->label('Item Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label('Available Quantity')
                    ->sortable(),
                ...(
                Auth::user()->can('view audit columns')
                    ? [
                        TextColumn::make('id')->label('Recorded ID')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('created_by')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                        TextColumn::make('updated_at')->label('Updated At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                    ]
                    : []
                    ),
            ])
            ->filters([
                Filter::make('item_code')
                    ->label('Item Code')
                    ->form([
                        Forms\Components\TextInput::make('item_code')
                            ->placeholder('Enter Item Code'),
                    ])
                    ->query(fn ($query, array $data) =>
                        $query->when($data['item_code'],
                            fn ($query, $itemCode) => $query->whereHas('item', fn ($q) =>
                                $q->where('item_code', 'like', "%{$itemCode}%")
                            )
                        )
                    ),

                Filter::make('warehouse')
                    ->label('Warehouse')
                    ->form([
                        Forms\Components\Select::make('warehouse_id')
                            ->options(\App\Models\Warehouse::pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Select Warehouse'),
                    ])
                    ->query(fn ($query, array $data) =>
                        $query->when($data['warehouse_id'],
                            fn ($query, $warehouseId) => $query->whereHas('location.warehouse', fn ($q) =>
                                $q->where('id', $warehouseId)
                            )
                        )
                    ),

                Filter::make('location')
                    ->label('Location')
                    ->form([
                        Forms\Components\Select::make('location_id')
                            ->options(
                                \App\Models\InventoryLocation::whereHas('warehouse', function ($q) {
                                    $q->whereIn('location_type', ['picking', 'shipment']);
                                })->pluck('name', 'id')
                            )
                            ->searchable()
                            ->placeholder('Select Picking/Shipment Location'),
                    ])
                    ->query(fn ($query, array $data) =>
                        $query->when($data['location_id'],
                            fn ($query, $locationId) => $query->where('location_id', $locationId)
                        )
                    ),
            ])
            ->actions([
                Action::make('getStocks')
                    ->label('Retrieve Stocks')
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading('Get Stocks')
                    ->modalWidth('md')
                    ->mountUsing(function ($form, $record) {
                        $form->fill([
                            'item_id' => $record->item_id,
                            'item_code' => $record->item->item_code,
                            'item_name' => $record->item->name,
                            'location_id' => $record->location_id,
                            'cost' => $record->cost,
                            'available_quantity' => $record->quantity,
                        ]);
                    })
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('item_id')->disabled(),
                            Forms\Components\TextInput::make('item_code')->label('Item Code')->disabled(),
                            Forms\Components\TextInput::make('item_name')->label('Item Name')->disabled(),
                            Forms\Components\TextInput::make('location_id')->label('Location ID')->disabled(),
                            Forms\Components\TextInput::make('cost')
                                ->label('Cost')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->readonly(),
                            Forms\Components\TextInput::make('available_quantity')->label('Available Quantity')->disabled(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantity to Get')
                                ->numeric()
                                ->required()
                                ->rules(['gt:0'])
                                ->rules(function (callable $get) {
                                    return ['lte:' . $get('available_quantity')];
                                }),
                            Forms\Components\Textarea::make('reason')
                                ->label('Reason')
                                ->required()
                                ->columnSpan(2)
                                ->rows(2),
                        ]),
                    ])
                    ->action(function ($record, array $data) {
                        \App\Models\StockGet::create([
                            'stock_id' => $record->id,
                            'item_id' => $record->item_id,
                            'location_id' => $record->location_id,
                            'quantity' => $data['quantity'],
                            'cost' => $data['cost'],
                            'reason' => $data['reason'],
                            'created_by' => auth()->id(),
                        ]);

                        $record->decrement('quantity', $data['quantity']);

                        Notification::make()
                            ->title('Stock Retrieved Successfully')
                            ->success()
                            ->send();
                    }),

                Action::make('returnStocks')
                    ->label('Return Stocks')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->modalHeading('Return Stocks')
                    ->modalWidth('md')
                    ->visible(fn ($record) => $record->purchase_order_id !== null)
                    ->mountUsing(function ($form, $record) {
                        $purchaseOrder = $record->purchaseOrder;

                        $form->fill([
                            'item_id' => $record->item_id,
                            'item_code' => $record->item->item_code,
                            'item_name' => $record->item->name,
                            'location_id' => $record->location_id,
                            'cost' => $record->cost,
                            'available_quantity' => $record->quantity,
                            'purchase_order_id' => $record->purchase_order_id,
                            'provider_type' => $purchaseOrder?->provider_type,
                            'provider_id' => $purchaseOrder?->provider_id,
                        ]);
                    })
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('item_id')->disabled(),
                            Forms\Components\TextInput::make('item_code')->label('Item Code')->disabled(),
                            Forms\Components\TextInput::make('item_name')->label('Item Name')->disabled(),
                            Forms\Components\TextInput::make('location_id')->label('Location ID')->disabled(),
                            Forms\Components\TextInput::make('cost')
                                ->label('Cost')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->readonly(),
                            Forms\Components\TextInput::make('available_quantity')->label('Available Quantity')->disabled(),
                            Forms\Components\TextInput::make('purchase_order_id')->label('PO ID')->disabled(),
                            Forms\Components\Hidden::make('provider_type')->required(),
                            Forms\Components\TextInput::make('provider_id')
                                ->label('Supplier ID')
                                ->readonly()
                                ->visible(fn (callable $get) => $get('provider_type') === 'supplier'),
                            Forms\Components\TextInput::make('provider_id')
                                ->label('Customer ID')
                                ->readonly()
                                ->visible(fn (callable $get) => $get('provider_type') === 'customer'),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantity to Return')
                                ->numeric()
                                ->required()
                                ->rules(['gt:0'])
                                ->rules(fn (callable $get) => ['lte:' . $get('available_quantity')]),
                            Forms\Components\Textarea::make('reason')
                                ->label('Reason')
                                ->required()
                                ->columnSpan(2)
                                ->rows(2),
                        ]),
                    ])
                    ->action(function ($record, array $data) {
                        \App\Models\StockReturn::create([
                            'stock_id' => $record->id,
                            'item_id' => $record->item_id,
                            'location_id' => $record->location_id,
                            'purchase_order_id' => $record->purchase_order_id,
                            'provider_type' => $data['provider_type'],
                            'provider_id' => $data['provider_id'],
                            'quantity' => $data['quantity'],
                            'cost' => $data['cost'],
                            'reason' => $data['reason'],
                            'created_by' => auth()->id(),
                        ]);

                        // Manually decrement quantity to be sure
                        $record->quantity = $record->quantity - $data['quantity'];
                        $record->save();

                        Notification::make()
                            ->title('Stock Returned Successfully')
                            ->success()
                            ->send();
                    }),

                Action::make('destroyStock')
                    ->label('Destroy Stock')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading('Destroy Stock')
                    ->modalWidth('md')
                    ->mountUsing(function ($form, $record) {
                        $form->fill([
                            'item_id' => $record->item_id,
                            'item_code' => $record->item->item_code,
                            'item_name' => $record->item->name,
                            'location_id' => $record->location_id,
                            'cost' => $record->cost,
                            'available_quantity' => $record->quantity,
                        ]);
                    })
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('item_id')->disabled(),
                            Forms\Components\TextInput::make('item_code')->label('Item Code')->disabled(),
                            Forms\Components\TextInput::make('item_name')->label('Item Name')->disabled(),
                            Forms\Components\TextInput::make('location_id')->label('Location ID')->disabled(),
                            Forms\Components\TextInput::make('cost')
                                ->label('Cost')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->readonly(),
                            Forms\Components\TextInput::make('available_quantity')->label('Available Quantity')->disabled(),
                            Forms\Components\TextInput::make('quantity')
                                ->label('Quantity to Destroy')
                                ->numeric()
                                ->required()
                                ->rules(['gt:0'])
                                ->rules(function (callable $get) {
                                    return ['lte:' . $get('available_quantity')];
                                }),
                            Forms\Components\Textarea::make('reason')
                                ->label('Reason')
                                ->required()
                                ->columnSpan(2)
                                ->rows(2),
                        ]),
                    ])
                    ->action(function ($record, array $data) {
                        \App\Models\StockDestroy::create([
                            'stock_id' => $record->id,
                            'item_id' => $record->item_id,
                            'location_id' => $record->location_id,
                            'quantity' => $data['quantity'],
                            'cost' => $data['cost'],
                            'reason' => $data['reason'],
                            'created_by' => auth()->id(),
                        ]);

                        $record->decrement('quantity', $data['quantity']);

                        Notification::make()
                            ->title('Stock Destroyed Successfully')
                            ->success()
                            ->send();
                    }),
                ])
            ->defaultSort('id', 'desc') 
            ->recordUrl(null);
    }


    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockOverview::route('/'),
            'create' => Pages\CreateStockOverview::route('/create'),
        ];
    }
}
