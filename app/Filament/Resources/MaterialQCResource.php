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
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Select;


class MaterialQCResource extends Resource
{
    protected static ?string $model = MaterialQC::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Section 1: Purchase Order Details
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

                                    if ($purchaseOrder && in_array($purchaseOrder->status, ['partially arrived', 'arrived'])) {
                                        $items = \App\Models\RegisterArrivalItem::whereHas('registerArrival', function ($query) use ($state) {
                                            $query->where('purchase_order_id', $state);
                                        })
                                        ->where('status', 'to be inspected')
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
                                            $set('location_name', $location?->name);
                                        }
                                    } else {
                                        $set('items', []);
                                        $set('provider_type', null);
                                        $set('provider_name', null);
                                        $set('provider_id', null);
                                        $set('wanted_date', null);
                                        $set('location_id', null);
                                        $set('location_name', null);
                                        $set('received_date', null);
                                        $set('invoice_number', null);

                                        \Filament\Notifications\Notification::make()
                                            ->title('Invalid Purchase Order Status')
                                            ->body('The purchase order status must be "partially arrived" or "arrived".')
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
                        ]),
                ]),

            // Section 2: Arrival Details
            Forms\Components\Section::make('Arrival Details')
                ->schema([
                    Forms\Components\Grid::make(2)
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
                        ]),
                ]),

            // Section 3: Items to Inspect
            Forms\Components\Section::make('Items to Inspect')
                ->schema([
                    Forms\Components\Repeater::make('items')
    ->columns(4)
    ->disableItemCreation()
    ->disableItemDeletion()
    ->schema([
        Forms\Components\Hidden::make('item_id')->required(),
        Forms\Components\TextInput::make('item_code')
            ->label('Item Code')
            ->disabled()
            ->columnSpan(1),

        Forms\Components\TextInput::make('quantity')
            ->label('Received Quantity')
            ->disabled()
            ->columnSpan(1),

        Forms\Components\TextInput::make('cost_of_item')
            ->label('Cost of Item')
            ->disabled()
            ->columnSpan(1),
        
            Forms\Components\Hidden::make('cost_of_item')
                ->default(function ($get) {
                    return $get('cost_of_item'); // Preserve the existing value
                }),

        Forms\Components\TextInput::make('inspected_quantity')
            ->label('Inspected Quantity')
            ->numeric()
            ->required()
            ->columnSpan(1),
            
        Forms\Components\TextInput::make('approved_qty')
            ->label('Approved Quantity')
            ->numeric()
            ->required()
            ->live()
            ->columnSpan(1),

        Forms\Components\TextInput::make('returned_qty')
            ->label('Returned Quantity')
            ->numeric()
            ->required()
            ->default(0)
            ->columnSpan(1),

        Forms\Components\TextInput::make('scrapped_qty')
            ->label('Scrapped Quantity')
            ->numeric()
            ->required()
            ->default(0)
            ->columnSpan(1),

        Select::make('inspected_by')
            ->label('QC Officer')
            ->options(User::whereHas('roles', fn($query) => 
                $query->where('name', 'Quality Control')
            )->pluck('name', 'id'))
            ->required()
            ->columnSpan(1),

        Select::make('store_location_id')
            ->label('Store Location for Approved Items')
            ->options(\App\Models\InventoryLocation::where('location_type', 'picking')->pluck('name', 'id'))
            ->required()
            ->columnSpan(1),
    ]),
                    ]),
        ]);
    }




    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Define columns here (if needed)
            ])
            ->filters([

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
