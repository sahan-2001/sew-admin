<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\Customer;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationGroup = 'Orders';
    protected static ?string $navigationLabel = 'Purchase Orders';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('provider_type')
                    ->label('Provider Type')
                    ->options([
                        'supplier' => 'Supplier',
                        'customer' => 'Customer',
                    ])
                    ->reactive()
                    ->required(),
                Select::make('provider_id')
                    ->label('Provider')
                    ->options(function ($get) {
                        if ($get('provider_type') === 'supplier') {
                            return Supplier::all()->pluck('name', 'supplier_id');
                        } elseif ($get('provider_type') === 'customer') {
                            return Customer::all()->pluck('name', 'customer_id');
                        }
                        return [];
                    })
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, $get) {
                        if ($get('provider_type') === 'supplier') {
                            $supplier = Supplier::find($state);
                            if ($supplier) {
                                $set('provider_name', $supplier->name);
                                $set('provider_email', $supplier->email);
                                $set('provider_phone', $supplier->phone_1);
                            } else {
                                $set('provider_name', null);
                                $set('provider_email', null);
                                $set('provider_phone', null);
                            }
                        } elseif ($get('provider_type') === 'customer') {
                            $customer = Customer::find($state);
                            if ($customer) {
                                $set('provider_name', $customer->name);
                                $set('provider_email', $customer->email);
                                $set('provider_phone', $customer->phone_1);
                            } else {
                                $set('provider_name', null);
                                $set('provider_email', null);
                                $set('provider_phone', null);
                            }
                        }
                    }),
                TextInput::make('provider_name')
                    ->label('Name')
                    ->required()
                    ->readonly(),
                TextInput::make('provider_email')
                    ->label('Email')
                    ->required()
                    ->readonly(),
                TextInput::make('provider_phone')
                    ->label('Phone')
                    ->required()
                    ->readonly(),
                DatePicker::make('wanted_date')
                    ->label('Wanted Delivery Date')
                    ->required(),
                Textarea::make('special_note')
                    ->label('Special Note')
                    ->nullable(),
                
                Section::make('Order Items')
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('inventory_item_id')
                                        ->label('Item')
                                        ->relationship('inventoryItem', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required(),

                                    TextInput::make('quantity')
                                        ->label('Quantity')
                                        ->numeric()
                                        ->required()
                                        ->live() // Ensures the state is updated immediately
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $set('remaining_quantity', $state); // Set remaining_quantity
                                            $set('arrived_quantity', 0); // Default arrived_quantity to 0
                                        }),

                                    TextInput::make('price')
                                        ->label('Price')
                                        ->numeric()
                                        ->required(),
                                    
                                    Hidden::make('remaining_quantity')
                                        ->default(fn ($get) => $get('quantity')),
                    
                                    Hidden::make('arrived_quantity')
                                        ->default(0),
                                ]),
                        ])
                        ->columns(1)
                        ->createItemButtonLabel('Add Order Item'),
                ]),
                Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('id')->label('Order ID'),
            TextColumn::make('provider_type')->label('Provider Type'),
            TextColumn::make('provider_id')->label('Provider ID'),
            TextColumn::make('provider_name')->label('Provider Name'),
            TextColumn::make('wanted_date')->label('Wanted Date')->date(),
            TextColumn::make('status')->label('Status')
                ->badge()
                ->colors([
                    'planned' => 'gray',
                    'released' => 'blue',
                    'cancelled' => 'red',
                    'completed' => 'green',
                ]),
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
            Action::make('handle')
                ->label('Handle')
                ->url(fn ($record) => PurchaseOrderResource::getUrl('handle', ['record' => $record]))
                ->openUrlInNewTab(false),

            EditAction::make()
                ->visible(fn ($record) => 
                    auth()->user()->can('edit purchase orders') &&
                    $record->status === 'planned'
                ),

            DeleteAction::make()
                ->visible(fn ($record) => 
                    auth()->user()->can('delete purchase orders') &&
                    in_array($record->status, ['planned', 'released'])
                ),
            
        ])
        ->defaultSort('wanted_date', 'desc')
        ->recordUrl(null);
}




    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
            'handle' => Pages\HandlePurchaseOrder::route('/{record}/handle'),
        ];
    }
}