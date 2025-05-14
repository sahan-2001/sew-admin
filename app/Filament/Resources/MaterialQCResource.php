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

    if ($purchaseOrder) {
        // Check if all RegisterArrivalItem records for the purchase order are 'inspected'
        $allInspected = \App\Models\RegisterArrivalItem::whereHas('registerArrival', function ($query) use ($state) {
            $query->where('purchase_order_id', $state);
        })
        ->where('status', '!=', 'inspected') // Check for any record NOT inspected
        ->doesntExist(); // If none exist, all are inspected

        if ($allInspected) {
            \Filament\Notifications\Notification::make()
                ->title('Purchase Order Inspected')
                ->body('All records for this purchase order have already been inspected.')
                ->danger()
                ->send();

            // Clear all form data
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

            return;
        }

        // If not all are inspected, proceed with existing logic
        if (in_array($purchaseOrder->status, ['partially arrived', 'arrived'])) {
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
                $set('register_arrival_id', $registerArrival->id); // Set the register_arrival_id

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
            $set('register_arrival_id', null);

            \Filament\Notifications\Notification::make()
                ->title('Invalid Purchase Order Status')
                ->body('The purchase order status must be "partially arrived" or "arrived".')
                ->danger()
                ->send();
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
        $set('register_arrival_id', null);

        \Filament\Notifications\Notification::make()
            ->title('Purchase Order Not Found')
            ->body('The purchase order ID entered does not exist.')
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
                            Forms\Components\Hidden::make('register_arrival_id')->required(),
                            #Forms\Components\TextInput::make('register_arrival_id')
                            #   ->label('Register Arrival ID')
                             #  ->disabled(),
                                
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
    ->columnSpan(1)
    ->reactive()
    ->afterStateUpdated(function ($state, callable $get, callable $set) {
        $quantity = $get('quantity');
        if ($state > $quantity) {
            $set('inspected_quantity', $quantity); // Reset to max allowed
            $set('inspected_quantity_error', 'Inspected Quantity cannot exceed Received Quantity.');
        } else {
            $set('inspected_quantity_error', null); // Clear error
        }
    })
    ->helperText(fn (callable $get) => $get('inspected_quantity_error')),

Forms\Components\TextInput::make('approved_qty')
    ->label('Approved Quantity')
    ->numeric()
    ->required()
    ->live()
    ->reactive()
    ->afterStateUpdated(function ($state, callable $get, callable $set) {
        $inspectedQuantity = $get('inspected_quantity') ?? 0;
        $returnedQty = $get('returned_qty') ?? 0;
        $scrappedQty = $get('scrapped_qty') ?? 0;

        if ($state + $returnedQty + $scrappedQty !== $inspectedQuantity) {
            $set('approved_qty_error', 'Approved Quantity + Returned Quantity + Scrapped Quantity must equal Inspected Quantity.');
        } else {
            $set('approved_qty_error', null); // Clear error
        }
    })
    ->helperText(fn (callable $get) => $get('approved_qty_error')),

Forms\Components\TextInput::make('returned_qty')
    ->label('Returned Quantity')
    ->numeric()
    ->required()
    ->default(0)
    ->live()
    ->reactive()
    ->afterStateUpdated(function ($state, callable $get, callable $set) {
        $inspectedQuantity = $get('inspected_quantity') ?? 0;
        $approvedQty = $get('approved_qty') ?? 0;
        $scrappedQty = $get('scrapped_qty') ?? 0;

        if ($approvedQty + $state + $scrappedQty !== $inspectedQuantity) {
            $set('returned_qty_error', 'Approved Quantity + Returned Quantity + Scrapped Quantity must equal Inspected Quantity.');
        } else {
            $set('returned_qty_error', null); // Clear error
        }
    })
    ->helperText(fn (callable $get) => $get('returned_qty_error')),

Forms\Components\TextInput::make('scrapped_qty')
    ->label('Scrapped Quantity')
    ->numeric()
    ->required()
    ->default(0)
    ->live()
    ->reactive()
    ->afterStateUpdated(function ($state, callable $get, callable $set) {
        $inspectedQuantity = $get('inspected_quantity') ?? 0;
        $approvedQty = $get('approved_qty') ?? 0;
        $returnedQty = $get('returned_qty') ?? 0;

        if ($approvedQty + $returnedQty + $state !== $inspectedQuantity) {
            $set('scrapped_qty_error', 'Approved Quantity + Returned Quantity + Scrapped Quantity must equal Inspected Quantity.');
        } else {
            $set('scrapped_qty_error', null); // Clear error
        }
    })
    ->helperText(fn (callable $get) => $get('scrapped_qty_error')),
        

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
                TextColumn::make('id')->label('QC Record ID'),
                TextColumn::make('purchase_order_id')->label('Purchase Order ID'),
                TextColumn::make('inspected_quantity')->label('Inspected Quantity'),
                TextColumn::make('approved_qty')->label('Approved Quantity'),
                TextColumn::make('returned_qty')->label('Returned Quantity'),
                TextColumn::make('scrapped_qty')->label('Scrapped Quantity'),
            ])
            ->filters([])
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
                    ->color('danger'),
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
