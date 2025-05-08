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
    return $form
        ->schema([
            // Section for Purchase Order Details
            Forms\Components\Card::make()
                ->schema([
                    Forms\Components\TextInput::make('purchase_order_id')
                        ->label('Purchase Order ID')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Query RegisterArrivalItem using the purchase_order_id
                            $items = \App\Models\RegisterArrivalItem::whereHas('registerArrival', function ($query) use ($state) {
                                $query->where('purchase_order_id', $state);
                            })
                            ->where('status', 'to be inspected')
                            ->get();

                            // Set the items to a form repeater or similar component
                            $set('items', $items->map(function ($item) {
                                $inventoryItem = \App\Models\InventoryItem::find($item->item_id);
                                return [
                                    'item_id' => $item->item_id,
                                    'item_code' => $inventoryItem ? $inventoryItem->item_code : null,
                                    'quantity' => $item->quantity,
                                    'status' => $item->status,
                                ];
                            })->toArray());

                            // Fetch and set Purchase Order details
                            $purchaseOrder = \App\Models\PurchaseOrder::where('id', $state)->first();
                            if ($purchaseOrder) {
                                $set('provider_type', $purchaseOrder->provider_type);
                                $set('provider_name', $purchaseOrder->provider_name);
                                $set('provider_id', $purchaseOrder->provider_id);
                                $set('wanted_date', $purchaseOrder->wanted_date);
                            }

                            // Fetch and set Register Arrival details
                            $registerArrival = \App\Models\RegisterArrival::where('purchase_order_id', $state)->first();
                            if ($registerArrival) {
                                $set('location_id', $registerArrival->location_id);
                                $set('received_date', $registerArrival->received_date);
                                $set('invoice_number', $registerArrival->invoice_number);

                                // Fetch and set Location Name
                                $location = \App\Models\InventoryLocation::where('id', $registerArrival->location_id)->first();
                                if ($location) {
                                    $set('location_name', $location->name);
                                }
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
                ->columns(2), // Display Purchase Order details in two columns

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
                ->columns(2), // Display Register Arrival details in two columns

            // Section for Register Arrival Items


            Forms\Components\Repeater::make('items')
    ->label('Items to Inspect')
    ->schema([
        Forms\Components\TextInput::make('item_id')
            ->label('Item ID')
            ->disabled(),
        Forms\Components\TextInput::make('item_code')
            ->label('Item Code')
            ->disabled(),
        Forms\Components\TextInput::make('quantity')
            ->label('Quantity')
            ->disabled(),
        Forms\Components\TextInput::make('status')
            ->label('Status')
            ->default('To Be Inspected')
            ->disabled(),
        Forms\Components\Placeholder::make('add_qc')
            ->label('Add QC Results')
            ->content('🔧')
            ->extraAttributes([
                'class' => 'cursor-pointer text-blue-500',
                'onclick' => 'Livewire.emit("openQCModal", "{{ $record["item_id"] }}")',
            ]),
    ])
    ->disableItemCreation()
    ->disableItemDeletion()
    ->columns(5)
    ->columnSpan('full')

        ]);

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
            //
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
