<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialQCResource\Pages;
use App\Filament\Resources\MaterialQCResource\RelationManagers;
use App\Models\MaterialQC;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Actions\Action;

class MaterialQCResource extends Resource
{
    protected static ?string $model = MaterialQC::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Section for Purchase Order Details
            Forms\Components\Card::make()
                ->schema([
                    Forms\Components\TextInput::make('purchase_order_id')
                        ->label('Purchase Order ID')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Fetch the purchase order details
                            $purchaseOrder = \App\Models\PurchaseOrder::find($state);
                            
                            // Check if the purchase order exists and if the status is 'partially arrived' or 'arrived'
                            if ($purchaseOrder && in_array($purchaseOrder->status, ['partially arrived', 'arrived'])) {
                                // Fetch RegisterArrivalItem data
                                $items = \App\Models\RegisterArrivalItem::whereHas('registerArrival', function ($query) use ($state) {
                                    $query->where('purchase_order_id', $state);
                                })
                                ->where('status', 'to be inspected')
                                ->get();

                                // Set the items to a form repeater
                                $set('items', $items->map(function ($item) {
                                    $inventoryItem = \App\Models\InventoryItem::find($item->item_id);
                                    return [
                                        'item_id' => $item->item_id,
                                        'item_code' => $inventoryItem ? $inventoryItem->item_code : null,
                                        'name' => $inventoryItem ? $inventoryItem->name : null,
                                        'quantity' => $item->quantity,
                                        'status' => $item->status,
                                    ];
                                })->toArray());

                                // Fetch and set additional details
                                $set('provider_type', $purchaseOrder->provider_type);
                                $set('provider_name', $purchaseOrder->provider_name);
                                $set('provider_id', $purchaseOrder->provider_id);
                                $set('wanted_date', $purchaseOrder->wanted_date);

                                $registerArrival = \App\Models\RegisterArrival::where('purchase_order_id', $state)->first();
                                if ($registerArrival) {
                                    $set('location_id', $registerArrival->location_id);
                                    $set('received_date', $registerArrival->received_date);
                                    $set('invoice_number', $registerArrival->invoice_number);
                                    
                                    $location = \App\Models\InventoryLocation::find($registerArrival->location_id);
                                    $set('location_name', $location ? $location->name : null);
                                }
                            } else {
                                // Clear the fields manually
                                $set('items', []);
                                $set('provider_type', null);
                                $set('provider_name', null);
                                $set('provider_id', null);
                                $set('wanted_date', null);
                                $set('location_id', null);
                                $set('location_name', null);
                                $set('received_date', null);
                                $set('invoice_number', null);

                                // Display a notification
                                \Filament\Notifications\Notification::make()
                                    ->title('Invalid Purchase Order Status')
                                    ->body('The purchase order status is not "partially arrived" or "arrived". Please select a valid purchase order.')
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Forms\Components\TextInput::make('provider_type')
                        ->label('Provider Type')
                        ->disabled(),
                    Forms\Components\TextInput::make('provider_name')
                        ->label('Provider Name')
                        ->disabled(),
                    Forms\Components\TextInput::make('provider_id')
                        ->label('Provider ID')
                        ->disabled(),
                    Forms\Components\DatePicker::make('wanted_date')
                        ->label('Wanted Date')
                        ->disabled(),
                ])
                ->columns(2),

            // Section for Register Arrival Details
            Forms\Components\Card::make()
                ->schema([
                    Forms\Components\TextInput::make('location_id')
                        ->label('Location ID')
                        ->disabled(),
                    Forms\Components\TextInput::make('location_name')
                        ->label('Location Name')
                        ->disabled(),
                    Forms\Components\DatePicker::make('received_date')
                        ->label('Received Date')
                        ->disabled(),
                    Forms\Components\TextInput::make('invoice_number')
                        ->label('Invoice Number')
                        ->disabled(),
                ])
                ->columns(2),

            Forms\Components\Repeater::make('items')
                ->label('Items to Inspect')
                ->schema([
                    Forms\Components\TextInput::make('item_id')
                        ->label('Item ID')
                        ->columnSpan(1)
                        ->disabled(),
                    Forms\Components\TextInput::make('item_code')
                        ->label('Item Code')
                        ->columnSpan(1)
                        ->disabled(),
                    Forms\Components\TextInput::make('name')
                        ->label('Item Name')
                        ->columnSpan(1)
                        ->disabled(),
                    Forms\Components\TextInput::make('quantity')
                        ->label('Quantity')
                        ->columnSpan(1)
                        ->disabled(),
                    Forms\Components\TextInput::make('status')
                        ->label('Status')
                        ->default('To Be Inspected')
                        ->columnSpan(1)
                        ->disabled(),
                ])
                ->columns(5)
                ->columnSpanFull()
                ->disableItemCreation()
                ->disableItemDeletion()
                ->disableLabel(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Define columns here (if needed)
            ])
            ->filters([
                // Add filters if necessary
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relations if necessary
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterialQCS::route('/'),
            'create' => Pages\CreateMaterialQC::route('/create'),
            'edit' => Pages\EditMaterialQC::route('/{record}/edit'),
        ];
    }
}
