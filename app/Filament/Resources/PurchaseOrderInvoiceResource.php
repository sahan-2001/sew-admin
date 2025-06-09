<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderInvoiceResource\Pages;
use App\Filament\Resources\PurchaseOrderInvoiceResource\RelationManagers;
use App\Models\PurchaseOrderInvoice;
use App\Models\PurchaseOrderInvoiceItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Notifications\Notification;

class PurchaseOrderInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseOrderInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Form Tabs')
                ->tabs([
                    Tab::make('Purchase Order')
                        ->schema([
                            Section::make('Purchase Order Details')->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('purchase_order_id')
                                        ->label('Purchase Order ID')
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $numericId = ltrim($state, '0');
                                            $purchaseOrder = \App\Models\PurchaseOrder::find($numericId);

                                            if (!$purchaseOrder) {
                                                Notification::make()
                                                    ->title('Purchase Order Not Found')
                                                    ->body('The purchase order ID entered does not exist.')
                                                    ->danger()
                                                    ->send();
                                                self::clearForm($set);
                                                return;
                                            }

                                            $set('provider_type', $purchaseOrder->provider_type);
                                            $set('provider_name', $purchaseOrder->provider_name);
                                            $set('provider_id', $purchaseOrder->provider_id);
                                            $set('wanted_date', $purchaseOrder->wanted_date);

                                            $registerArrivals = \App\Models\RegisterArrival::where('purchase_order_id', $numericId)->get();

                                            $set('register_arrival_options', $registerArrivals->mapWithKeys(function ($r) {
                                                $location = \App\Models\InventoryLocation::find($r->location_id);
                                                $locationName = $location ? $location->name : 'N/A';
                                                return [
                                                    $r->id => "ID - {$r->id} | Location - {$locationName} | Received Date - {$r->received_date}"
                                                ];
                                            })->toArray());
                                        }),

                                    Select::make('register_arrival_id')
                                        ->label('Register Arrival ID')
                                        ->options(fn (callable $get) => $get('register_arrival_options') ?? [])
                                        ->disabled(fn (callable $get) => empty($get('register_arrival_options')))
                                        ->reactive()
                                        ->required()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            // Fetch RegisterArrivalItems
                                            $items = \App\Models\RegisterArrivalItem::where('register_arrival_id', $state)->get();

                                            $set('items', $items->map(function ($item) {
                                                $inventoryItem = \App\Models\InventoryItem::find($item->item_id);
                                                return [
                                                    'item_id' => $item->item_id,
                                                    'item_code' => $inventoryItem?->item_code,
                                                    'name' => $inventoryItem?->name,
                                                    'quantity' => $item->quantity,
                                                    'price' => $item->price,
                                                    'status' => $item->status,
                                                ];
                                            })->toArray());

                                            $purchaseOrderId = ltrim($get('purchase_order_id'), '0');
                                            $qcRecords = \App\Models\MaterialQC::where('register_arrival_id', $state)
                                                ->where('purchase_order_id', $purchaseOrderId)
                                                ->get();

                                            $set('material_qc_items', $qcRecords->map(function ($record) {
                                                $item = \App\Models\InventoryItem::find($record->item_id);
                                                $storeLocation = \App\Models\InventoryLocation::find($record->store_location_id);

                                                return [
                                                    'item_code' => $item?->item_code,
                                                    'inspected_quantity' => $record->inspected_quantity,
                                                    'approved_qty' => $record->approved_qty,
                                                    'returned_qty' => $record->returned_qty,
                                                    'scrapped_qty' => $record->scrapped_qty,

                                                    'add_returned' => $record->add_returned,
                                                    'add_scrap' => $record->add_scrap,
                                                    'available_to_store' => $record->available_to_store,

                                                    'cost_of_item' => $record->cost_of_item,
                                                    'store_location' => $storeLocation?->name,

                                                    'total_returned' => (float) ($record->returned_qty ?? 0) + (float) ($record->add_returned ?? 0),
                                                    'total_scrapped' => (float) ($record->scrapped_qty ?? 0) + (float) ($record->add_scrap ?? 0),
                                                ];
                                            })->toArray());

                                            // Fetch Invoice Items
                                            $invoiceItems = collect();

                                            $passedItems = \App\Models\RegisterArrivalItem::where('register_arrival_id', $state)
                                                ->where('status', 'QC Passed')
                                                ->get();

                                            foreach ($passedItems as $item) {
                                            $invItem = \App\Models\InventoryItem::find($item->item_id);

                                            // Get the related RegisterArrival to access location_id
                                            $registerArrival = \App\Models\RegisterArrival::find($item->register_arrival_id);
                                            $location = \App\Models\InventoryLocation::find($registerArrival?->location_id);

                                            $invoiceItems->push([
                                                'item_id_i' => $item->item_id,
                                                'item_code_i' => $invItem?->item_code,
                                                'item_name_i' => $invItem?->name,
                                                'stored_quantity_i' => $item->quantity,
                                                'location_id_i' => $registerArrival?->location_id,
                                                'location_name_i' => $location?->name,
                                                'price_i' => $item->price,
                                            ]);
                                        }

                                            $materialQCs = \App\Models\MaterialQC::where('register_arrival_id', $state)
                                                ->where('purchase_order_id', $purchaseOrderId)
                                                ->get();

                                            foreach ($materialQCs as $qc) {
                                                if ($qc->available_to_store > 0) {
                                                    $invItem = \App\Models\InventoryItem::find($qc->item_id);
                                                    $location = \App\Models\InventoryLocation::find($qc->store_location_id);

                                                    $invoiceItems->push([
                                                        'item_id_i' => $qc->item_id,
                                                        'item_code_i' => $invItem?->item_code,
                                                        'item_name_i' => $invItem?->name,
                                                        'stored_quantity_i' => $qc->available_to_store,
                                                        'location_id_i' => $qc->store_location_id,
                                                        'location_name_i' => $location?->name,
                                                        'price_i' => $qc->cost_of_item, 
                                                    ]);
                                                }
                                            }
                                            $set('invoice_items', $invoiceItems->toArray());

                                        }),

                                    TextInput::make('provider_type')->label('Provider Type')->disabled()->dehydrated(true),
                                    TextInput::make('provider_id')->label('Provider ID')->disabled()->dehydrated(true),
                                    TextInput::make('provider_name')->label('Provider Name')->disabled(),
                                    TextInput::make('wanted_date')->label('Wanted Date')->disabled(),
                                ]),
                                Repeater::make('items')
                                    ->label('Items in Register Arrival')
                                    ->schema([
                                        TextInput::make('item_id')->label('Item ID')->disabled()->hidden(),
                                        TextInput::make('item_code')->label('Item Code')->disabled(),
                                        TextInput::make('name')->label('Item Name')->disabled(),
                                        TextInput::make('quantity')->label('Received Quantity')->disabled(),
                                        TextInput::make('price')->label('Unit Price')->disabled(),
                                        TextInput::make('status')->label('QC Status')->disabled(),
                                    ])
                                    ->columnSpan('full')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columns(5),
                            ]),
                        ]),

                    Tab::make('Material QC')
                        ->schema([
                            Section::make('Material QC Records')
                                ->schema([
                                    Repeater::make('material_qc_items')
                                        ->schema([
                                            TextInput::make('item_code')->label('Item Code')->disabled(),
                                            TextInput::make('inspected_quantity')->label('Inspected Qty')->disabled()->live(),
                                            TextInput::make('approved_qty')->label('Approved Qty')->disabled(),
                                            TextInput::make('returned_qty')->label('Returned Qty')->disabled(),
                                            TextInput::make('scrapped_qty')->label('Scrapped Qty')->disabled(),

                                            TextInput::make('total_returned')->label('Total Returned Qty')->disabled(),
                                            TextInput::make('total_scrapped')->label('Total Scrapped Qty')->disabled(),
                                            TextInput::make('available_to_store')->label('Total Stored Qty')->disabled(),
                                            TextInput::make('store_location')->label('Stored Location')->disabled(),
                                        ])
                                        ->columns(5)
                                        ->disabled()
                                        ->dehydrated(false),
                                ]),
                        ]),
                        
                    Tab::make('Invoice Details')
                        ->schema([
                            Section::make('Final Invoice Items')
                                ->schema([
                                    Repeater::make('invoice_items')
                                        ->schema([
                                            TextInput::make('item_id_i')->label('Item ID')->disabled()->dehydrated(),
                                            TextInput::make('item_code_i')->label('Item Code')->disabled(),
                                            TextInput::make('item_name_i')->label('Item Name')->disabled(),
                                            TextInput::make('stored_quantity_i')->label('Stored Quantity')->disabled()->dehydrated(),
                                            TextInput::make('location_id_i')->label('Location ID')->disabled()->dehydrated(),
                                            TextInput::make('location_name_i')->label('Location Name')->disabled(),
                                            TextInput::make('price_i')->label('Unit Price')->disabled()->dehydrated(),
                                        ])
                                        ->columns(4)
                                        ->disabled()
                                        ->dehydrated(),
                                ]),
                        ]),
                ])
                ->columnspanFull(),
        ]);
    }


    protected static function clearForm(callable $set): void
    {
        $set('items', []);
        $set('provider_type', null);
        $set('provider_name', null);
        $set('provider_id', null);
        $set('wanted_date', null);
        $set('location_id', null);
        $set('location_name', null);
        $set('received_date', null);
        $set('invoice_number', null);
        $set('register_arrival_id', null);
        $set('register_arrival_options', []);
        $set('material_qc_items', []);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
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
            'index' => Pages\ListPurchaseOrderInvoices::route('/'),
            'create' => Pages\CreatePurchaseOrderInvoice::route('/create'),
        ];
    }
}
