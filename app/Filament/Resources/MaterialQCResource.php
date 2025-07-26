<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialQCResource\Pages;
use App\Filament\Resources\MaterialQCResource\RelationManagers;
use App\Models\MaterialQC;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\TextFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class MaterialQCResource extends Resource
{
    protected static ?string $model = MaterialQC::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Quality Control';
    protected static ?string $label = 'Material QC';
    protected static ?string $pluralLabel = 'Material QCs';
    protected static ?string $navigationLabel = 'Material QC';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Material QC Entry')
                ->tabs([
                    Tab::make('Arrival Info')
                        ->schema([
                            Forms\Components\Section::make('Purchase Order Details')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('purchase_order_id')
                                                ->label('Purchase Order ID')
                                                ->required()
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    $purchaseOrder = \App\Models\PurchaseOrder::find($state);

                                                    // Reset form state
                                                    $set('register_arrival_id', null);
                                                    $set('items', []);
                                                    $set('provider_type', null);
                                                    $set('provider_id', null);
                                                    $set('wanted_date', null);
                                                    $set('location_id', null);
                                                    $set('location_name', null);
                                                    $set('received_date', null);
                                                    $set('invoice_number', null);

                                                    if (!$purchaseOrder) {
                                                        Notification::make()
                                                            ->title('Purchase Order Not Found')
                                                            ->body('The purchase order ID entered does not exist.')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }

                                                    if (!in_array($purchaseOrder->status, ['arrived', 'partially arrived'])) {
                                                        Notification::make()
                                                            ->title('Invalid Purchase Order Status')
                                                            ->body('Purchase order status must be "arrived" or "partially arrived".')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }

                                                    // Set supplier info
                                                    $set('provider_type', $purchaseOrder->provider_type);
                                                    $set('provider_id', $purchaseOrder->provider_id);
                                                    $set('wanted_date', $purchaseOrder->wanted_date);
                                                }),

                                            Forms\Components\TextInput::make('provider_type')->label('Provider Type')->disabled(),
                                            Forms\Components\TextInput::make('provider_id')->label('Provider ID')->disabled(),
                                            Forms\Components\TextInput::make('wanted_date')->label('Wanted Date')->disabled(),
                                        ])
                                    ]),        
                                        
                                    Forms\Components\Section::make('Register Arrival Details')
                                        ->schema([    
                                            Forms\Components\Select::make('register_arrival_id')
                                                ->label('Register Arrival ID')
                                                ->options(fn (callable $get) =>
                                                    \App\Models\RegisterArrival::where('purchase_order_id', $get('purchase_order_id'))
                                                        ->whereHas('items', function($query) {
                                                            $query->where('status', 'to be inspected')
                                                                  ->where('quantity', '>', 0);
                                                        })
                                                        ->get()
                                                        ->mapWithKeys(function ($arrival) {
                                                            $location = \App\Models\InventoryLocation::find($arrival->location_id);
                                                            return [
                                                                $arrival->id =>
                                                                    'ID: ' . $arrival->id .
                                                                    ' | Location: ' . ($location?->name ?? 'Unknown') .
                                                                    ' | Date: ' . $arrival->received_date,
                                                            ];
                                                        })
                                                        ->toArray()
                                                )
                                                ->required()
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                    $set('items', []);
                                                    $registerArrival = \App\Models\RegisterArrival::find($state);

                                                    if (!$registerArrival) {
                                                        return;
                                                    }

                                                    $set('items', []);

                                                    $registerArrival = \App\Models\RegisterArrival::find($state);

                                                    if (!$registerArrival) {
                                                        return;
                                                    }

                                                    // Validate if all items for this arrival are already inspected
                                                    $allInspected = \App\Models\RegisterArrivalItem::where('register_arrival_id', $state)
                                                        ->where('status', '!=', 'inspected')
                                                        ->doesntExist();

                                                    if ($allInspected) {
                                                        Notification::make()
                                                            ->title('Arrival Already Inspected')
                                                            ->body('All items under this arrival record have already been inspected.')
                                                            ->danger()
                                                            ->send();

                                                        return;
                                                    }  

                                                    // Validate if all items for this arrival are already Passed
                                                    $allInspected = \App\Models\RegisterArrivalItem::where('register_arrival_id', $state)
                                                        ->where('status', '!=', 'QC Passed')
                                                        ->doesntExist();

                                                    if ($allInspected) {
                                                        Notification::make()
                                                            ->title('Arrival Items Already QC passed')
                                                            ->body('All items under this arrival record have already been Passed (QC Passed).')
                                                            ->danger()
                                                            ->send();

                                                        return;
                                                    }  
                                                           
                                                           
                                                    // Set register arrival meta
                                                    $set('location_id', $registerArrival->location_id);
                                                    $set('received_date', $registerArrival->received_date);
                                                    $set('invoice_number', $registerArrival->invoice_number);

                                                    $location = \App\Models\InventoryLocation::find($registerArrival->location_id);
                                                    $set('location_name', $location?->name);

                                                    // Load items with status = 'to be inspected' and quantity > 0
                                                    $items = \App\Models\RegisterArrivalItem::where('register_arrival_id', $state)
                                                        ->where('status', 'to be inspected')
                                                        ->where('quantity', '>', 0)
                                                        ->get();

                                                    $set('items', $items->map(function ($item) {
                                                        $inventoryItem = \App\Models\InventoryItem::find($item->item_id);
                                                        return [
                                                            'item_id' => $item->item_id,
                                                            'item_code' => $inventoryItem?->item_code,
                                                            'name' => $inventoryItem?->name,
                                                            'quantity' => $item->quantity,
                                                            'cost_of_item' => $item->price ?? 0,
                                                            'status' => $item->status,
                                                        ];
                                                    })->toArray());
                                                }),
                                            
                                            Forms\Components\TextInput::make('location_name')->label('Location')->disabled(),
                                            Forms\Components\TextInput::make('received_date')->label('Received Date')->disabled(),
                                            Forms\Components\TextInput::make('invoice_number')->label('Invoice Number')->disabled(),
                                ])
                                ->columns(2),

                                Forms\Components\Section::make('Arrival Items Details')
                                    ->schema([
                                        Forms\Components\Repeater::make('items')
                                            ->columns(5)
                                            ->schema([
                                                Forms\Components\TextInput::make('item_code')
                                                    ->label('Item Code')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Item Name')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Quantity')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('cost_of_item')
                                                    ->label('Cost')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('status')
                                                    ->label('Status')
                                                    ->disabled(),
                                            ])
                                            ->disableItemCreation()
                                            ->disableItemDeletion(),
                                    ]),
                            ]),

                        Tab::make('Items to Inspect')
                            ->schema([
                                Forms\Components\Section::make('Items to Inspect')
                                    ->schema([
                                        Forms\Components\Repeater::make('items')
                                            ->columns(4)
                                            ->disableItemCreation()
                                            ->disableItemDeletion()
                                            ->schema([
                                                Forms\Components\Hidden::make('item_id')->required(),
                                                Forms\Components\TextInput::make('item_code')->label('Item Code')->disabled(),
                                                Forms\Components\TextInput::make('quantity')->label('Received Quantity')->disabled(),
                                                Forms\Components\TextInput::make('cost_of_item')->label('Cost of Item')->disabled(),

                                                Forms\Components\Hidden::make('cost_of_item')->default(fn ($get) => $get('cost_of_item')),

                                                Forms\Components\TextInput::make('inspected_quantity')
                                                    ->label('Inspected Quantity')
                                                    ->numeric()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                        $quantity = $get('quantity');
                                                        if ($state > $quantity) {
                                                            $set('inspected_quantity', $quantity);
                                                            $set('inspected_quantity_error', 'Inspected Quantity cannot exceed Received Quantity.');
                                                        } else {
                                                            $set('inspected_quantity_error', null);
                                                        }
                                                    })
                                                    ->helperText(fn (callable $get) => $get('inspected_quantity_error')),

                                                Forms\Components\TextInput::make('approved_qty')
                                                    ->label('Approved Quantity')
                                                    ->numeric()
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                        $approved = (float) ($state ?? 0);
                                                        $returned = (float) ($get('returned_qty') ?? 0);
                                                        $inspected = (float) ($get('inspected_quantity') ?? 0);
                                                        $scrapped = (float) ($get('scrapped_qty') ?? 0);

                                                        if (($approved + $returned) > $inspected) {
                                                            $set('approved_qty_error', 'Approved + Returned cannot exceed Inspected Quantity.');
                                                        } elseif (($approved + $returned + $scrapped) !== $inspected && $scrapped > 0) {
                                                            $set('approved_qty_error', 'Approved + Returned + Scrapped must equal Inspected Quantity.');
                                                        } else {
                                                            $set('approved_qty_error', null);
                                                        }
                                                    })
                                                    ->helperText(fn (callable $get) => $get('approved_qty_error')),

                                                Forms\Components\TextInput::make('returned_qty')
                                                    ->label('Returned Quantity')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                        $returned = (float) ($state ?? 0);
                                                        $approved = (float) ($get('approved_qty') ?? 0);
                                                        $inspected = (float) ($get('inspected_quantity') ?? 0);
                                                        $scrapped = (float) ($get('scrapped_qty') ?? 0);

                                                        $available = ($get('quantity') ?? 0) - ($returned + $scrapped);
                                                        $set('available_to_store', max($available, 0));
                                                        $set('total_returned_qc', $returned);

                                                        if (($approved + $returned) > $inspected) {
                                                            $set('approved_qty_error', 'Approved + Returned cannot exceed Inspected Quantity.');
                                                            $set('returned_qty', null);
                                                        } elseif (($approved + $returned + $scrapped) !== $inspected && $scrapped > 0) {
                                                            $set('approved_qty_error', 'Approved + Returned + Scrapped must equal Inspected Quantity.');
                                                        } else {
                                                            $set('approved_qty_error', null);
                                                        }
                                                    }),

                                                Forms\Components\TextInput::make('scrapped_qty')
                                                    ->label('Scrapped Quantity')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                        $scrapped = (float) ($state ?? 0);
                                                        $approved = (float) ($get('approved_qty') ?? 0);
                                                        $returned = (float) ($get('returned_qty') ?? 0);
                                                        $inspected = (float) ($get('inspected_quantity') ?? 0);

                                                        $available = ($get('quantity') ?? 0) - ($returned + $scrapped);
                                                        $set('available_to_store', max($available, 0));
                                                        $set('total_scrapped_qc', $scrapped);

                                                        if (($approved + $returned + $scrapped) !== $inspected) {
                                                            $set('approved_qty_error', 'Approved + Returned + Scrapped must equal Inspected Quantity.');
                                                            $set('returned_qty', null);
                                                            $set('scrapped_qty', null);
                                                        } else {
                                                            $set('approved_qty_error', null);
                                                        }
                                                    }),


                                               Select::make('inspected_by')
                                                    ->label('QC Officer')
                                                    ->options(
                                                        User::whereHas('roles', fn ($query) =>
                                                            $query->where('name', 'Quality Control')
                                                        )
                                                        ->get()
                                                        ->mapWithKeys(fn ($user) => [
                                                            $user->id => "{$user->id} | {$user->name}"
                                                        ])
                                                    )
                                                    ->required(),
                                            ]),
                                    ]),
                            ]),

                            Tab::make('Store QC Passed Items')
                            ->schema([
                                Forms\Components\Section::make('Store QC Items')
                                    ->schema([
                                        Forms\Components\Repeater::make('items')
                                            ->columns(5)
                                            ->schema([
                                                Forms\Components\TextInput::make('item_code')
                                                    ->label('Item Code')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Quantity')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('total_returned_qc')
                                                    ->label('Total Returned from QC')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->disabled()
                                                    ->live()
                                                    ->dehydrated(),

                                                Forms\Components\TextInput::make('total_scrapped_qc')
                                                    ->label('Total Scrapped from QC')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->disabled()
                                                    ->live()
                                                    ->dehydrated(),

                                                Forms\Components\TextInput::make('add_returned')
                                                    ->label('Additional Return Items')
                                                    ->numeric()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                        $availableToStore = 
                                                            (float) ($get('quantity') ?? 0)
                                                            - ((float) ($get('total_returned_qc') ?? 0) + (float) ($state ?? 0))
                                                            - ((float) ($get('total_scrapped_qc') ?? 0) + (float) ($get('add_scrap') ?? 0));

                                                        if ($availableToStore < 0) {
                                                            $set('add_returned', null);
                                                            $set('add_scrap', null);
                                                        }
                                                    })
                                                    ->dehydrated(true),

                                                Forms\Components\TextInput::make('add_scrap')
                                                    ->label('Additional Scrapped Items')
                                                    ->numeric()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                        $availableToStore = 
                                                            (float) ($get('quantity') ?? 0)
                                                            - ((float) ($get('total_returned_qc') ?? 0) + (float) ($get('add_returned') ?? 0))
                                                            - ((float) ($get('total_scrapped_qc') ?? 0) + (float) ($state ?? 0));

                                                        if ($availableToStore < 0) {
                                                            $set('add_returned', null);
                                                            $set('add_scrap', null);
                                                        }
                                                    })
                                                    ->dehydrated(true),
                                                
                                                Forms\Components\Placeholder::make('total_returned')
                                                    ->label('Total Returned')
                                                    ->live()
                                                    ->content(fn (callable $get) => 
                                                        (float) ($get('total_returned_qc') ?? 0) + (float) ($get('add_returned') ?? 0)
                                                    )
                                                    ->dehydrated(true),
                                                Forms\Components\Placeholder::make('total_scrapped')
                                                    ->label('Total Scrapped')
                                                    ->live()
                                                    ->content(fn (callable $get) => 
                                                        (float) ($get('total_scrapped_qc') ?? 0) + (float) ($get('add_scrap') ?? 0)
                                                    )
                                                    ->dehydrated(true),
                                
                                                Forms\Components\Placeholder::make('available')
                                                    ->label('Available to Store')
                                                    ->live()
                                                    ->content(function (callable $get, callable $set) {
                                                        $qty = (float) ($get('quantity') ?? 0);
                                                        $returned = (float) ($get('total_returned_qc') ?? 0) + (float) ($get('add_returned') ?? 0);
                                                        $scrapped = (float) ($get('total_scrapped_qc') ?? 0) + (float) ($get('add_scrap') ?? 0);
                                                        $available = $qty - $returned - $scrapped;

                                                        // Set the hidden field
                                                        $set('available_to_store', $available);

                                                        return $available;
                                                    })
                                                    ->dehydrated(false),

                                                Forms\Components\TextInput::make('available_to_store')
                                                    ->label('Available to Store')
                                                    ->hidden()
                                                    ->disabled()
                                                    ->dehydrated(),
        
                                                Select::make('store_location_id')
                                                    ->label('Store Location')
                                                    ->required()
                                                    ->options(function () {
                                                        return \App\Models\InventoryLocation::with('warehouse')
                                                            ->where('location_type', 'picking')
                                                            ->get()
                                                            ->mapWithKeys(function ($location) {
                                                                $warehouseId = $location->warehouse?->id ?? 'N/A';
                                                                $locationId = $location->id;
                                                                $locationName = $location->name;

                                                                $label = "{$locationId} | {$locationName}";

                                                                return [$locationId => $label];
                                                            });
                                                    }),
                                            ])
                                            ->disableItemCreation()
                                            ->disableItemDeletion(),
                                    ]),
                            ]),
                    ])
                    ->columnspanFull(),
        ]);
    }


    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('QC Record ID')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('purchase_order_id')
                    ->label('Purchase Order ID')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('inspected_quantity')
                    ->label('Inspected Quantity')
                    ->formatStateUsing(fn ($state) => (is_numeric($state) && floor($state) != $state) ? number_format($state, 2) : number_format($state, 0)),
                TextColumn::make('approved_qty')
                    ->label('Approved Quantity')
                    ->formatStateUsing(fn ($state) => (is_numeric($state) && floor($state) != $state) ? number_format($state, 2) : number_format($state, 0)),
                TextColumn::make('returned_qty')
                    ->label('Returned Quantity')
                    ->formatStateUsing(fn ($state) => (is_numeric($state) && floor($state) != $state) ? number_format($state, 2) : number_format($state, 0)),
                TextColumn::make('scrapped_qty')
                    ->label('Scrapped Quantity')
                    ->formatStateUsing(fn ($state) => (is_numeric($state) && floor($state) != $state) ? number_format($state, 2) : number_format($state, 0)),
                TextColumn::make('status')->label('Status'),

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
                Filter::make('id')
                    ->label('QC Record ID')
                    ->form([
                        Forms\Components\TextInput::make('id')
                            ->placeholder('Enter QC Record ID'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['id'] ?? null,
                            fn ($query, $id) => $query->where('id', 'like', "%{$id}%")
                        );
                    }),

                Filter::make('purchase_order_id')
                    ->label('Purchase Order ID')
                    ->form([
                        Forms\Components\TextInput::make('purchase_order_id')
                            ->placeholder('Enter Purchase Order ID'),
                    ])
                    ->query(fn ($query, $data) =>
                        $query->when(
                            $data['purchase_order_id'] ?? null,
                            fn ($query, $poId) => $query->where('purchase_order_id', 'like', "%{$poId}%")
                        )
                    ),

                Filter::make('status')
                    ->label('Status')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'invoiced' => 'Invoiced',
                                // add other statuses you have
                            ])
                            ->placeholder('Select status'),
                    ])
                    ->query(fn ($query, $data) =>
                        $query->when(
                            $data['status'] ?? null,
                            fn ($query, $status) => $query->where('status', $status)
                        )
                    ),
            ])
            ->actions([
                Action::make('reCorrection')
                    ->label('Re-Correction')
                    ->authorize(fn ($record) => 
                        auth()->user()->can('re-correct material qc') 
                    )
                    ->action(function (MaterialQC $record) {
                        // Begin transaction
                        \DB::beginTransaction();

                        try {
                            // Update the status of related RegisterArrivalItem records to "to be inspected"
                            \App\Models\RegisterArrivalItem::where('register_arrival_id', $record->register_arrival_id)
                                ->where('item_id', $record->item_id)
                                ->update(['status' => 'to be inspected']);

                            // Delete related rows in the Stock table
                            \App\Models\Stock::where('purchase_order_id', $record->purchase_order_id)
                                ->where('item_id', $record->item_id)
                                ->delete();

                            // Delete the MaterialQC record
                            $record->delete();

                            // Commit transaction
                            \DB::commit();

                            // Notify the user
                            \Filament\Notifications\Notification::make()
                                ->title('Re-Correction Successful')
                                ->body('The record has been reset and related data has been updated.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            // Rollback transaction on error
                            \DB::rollBack();

                            // Notify the user of the error
                            \Filament\Notifications\Notification::make()
                                ->title('Re-Correction Failed')
                                ->body('An error occurred while performing the re-correction: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== 'invoiced'),  
            ])
            ->defaultSort('id', 'desc') 
            ->recordUrl(null);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterialQCS::route('/'),
            'create' => Pages\CreateMaterialQC::route('/create'),
        ];
    }
}
