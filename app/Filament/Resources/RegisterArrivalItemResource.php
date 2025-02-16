<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegisterArrivalItemResource\Pages;
use App\Models\RegisterArrivalItem;
use App\Models\RegisterArrivalItemDescription;
use App\Models\PurchaseOrder;
use App\Models\InventoryItem;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;

class RegisterArrivalItemResource extends Resource
{
    protected static ?string $model = RegisterArrivalItem::class;

    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Register Arrival Items';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('purchase_order_id')
                    ->label('Purchase Order ID')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $purchaseOrder = PurchaseOrder::with('items.inventoryItem')->find($state);

                        if ($purchaseOrder) {
                            $set('provider_type', $purchaseOrder->provider_type);
                            $set('provider_id', $purchaseOrder->provider_id);
                            $set('provider_name', $purchaseOrder->provider_name);
                            $set('provider_email', $purchaseOrder->provider_email);
                            $set('provider_phone', $purchaseOrder->provider_phone);

                            // Prefill items from the purchase order
                            $set('purchase_order_items', $purchaseOrder->items->map(function ($item) {
                                return [
                                    'item_code' => $item->inventoryItem->item_code,
                                    'item_name' => $item->inventoryItem->name,
                                    'quantity' => $item->quantity,
                                    'price' => $item->price,
                                    'total' => $item->quantity * $item->price,
                                    'is_po_item' => 'yes', // Mark as purchase order item
                                ];
                            })->toArray());
                        } else {
                            $set('purchase_order_items', []);
                        }
                    }),

                TextInput::make('provider_type')->label('Provider Type')->disabled(),
                TextInput::make('provider_id')->label('Provider ID')->disabled(),
                TextInput::make('provider_name')->label('Provider Name')->disabled(),
                TextInput::make('provider_email')->label('Provider Email')->disabled(),
                TextInput::make('provider_phone')->label('Provider Phone')->disabled(),

                DatePicker::make('received_date')->label('Received Date')->required(),
                TextInput::make('invoice_number')->label('Invoice Number')->required(),
                FileUpload::make('invoice_image')->label('Invoice Image')->nullable(),
                Textarea::make('note')->label('Note')->nullable(),

                Select::make('location_status')
                    ->label('Location Status')
                    ->options([
                        'arrival' => 'Arrival',
                        'picking' => 'Picking',
                        'shipment' => 'Shipment',
                    ])
                    ->default('arrival'),

                Section::make('Purchase Order Items')
                    ->hidden(fn ($get) => empty($get('purchase_order_id')))
                    ->schema([
                        Repeater::make('purchase_order_items')
                            ->schema([
                                Grid::make(5)
                                    ->schema([
                                        TextInput::make('item_code')->label('Item Code')->disabled(),
                                        TextInput::make('item_name')->label('Item Name')->disabled(),
                                        TextInput::make('quantity')->label('Quantity')->numeric()->required(),
                                        TextInput::make('price')->label('Price')->numeric()->required(),
                                        TextInput::make('total')
                                            ->label('Total')
                                            ->numeric()
                                            ->disabled()
                                            ->default(fn ($get) => $get('quantity') * $get('price')),
                                    ]),
                            ])
                            ->columns(1)
                            ->disableItemCreation()
                            ->disableItemMovement()
                            ->disableItemDeletion(),
                    ]),

                Section::make('Items Not in the Purchase Order')
                    ->schema([
                        Repeater::make('additional_items')
                            ->schema([
                                Grid::make(5)
                                    ->schema([
                                        Select::make('item_name')
                                            ->label('Item Name')
                                            ->options(InventoryItem::pluck('name', 'id'))
                                            ->searchable()
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $set, $state) {
                                                $inventoryItem = InventoryItem::find($state);
                                                if ($inventoryItem) {
                                                    $set('item_code', $inventoryItem->item_code);
                                                }
                                            }),
                                        TextInput::make('item_code')->label('Item Code')->disabled(),
                                        TextInput::make('quantity')->label('Quantity')->numeric()->required(),
                                        TextInput::make('price')->label('Price')->numeric()->required(),
                                        TextInput::make('total')
                                            ->label('Total')
                                            ->numeric()
                                            ->disabled()
                                            ->default(fn ($get) => $get('quantity') * $get('price')),
                                    ]),
                            ])
                            ->columns(1)
                            ->createItemButtonLabel('Add New Item'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('purchase_order_id')->label('Purchase Order ID'),
                TextColumn::make('received_date')->label('Received Date'),
                TextColumn::make('invoice_number')->label('Invoice Number'),
                TextColumn::make('location_status')->label('Location Status'),
                TextColumn::make('created_at')->label('Created Date')->dateTime(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegisterArrivalItems::route('/'),
            'create' => Pages\CreateRegisterArrivalItem::route('/create'),
            'edit' => Pages\EditRegisterArrivalItem::route('/{record}/edit'),
        ];
    }

    
}
