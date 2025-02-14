<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerOrderResource\Pages;
use App\Models\CustomerOrder;
use App\Models\Customer;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\Hidden;

class CustomerOrderResource extends Resource
{
    protected static ?string $model = CustomerOrder::class;

    protected static ?string $navigationGroup = 'Orders';
    protected static ?string $navigationLabel = 'Customer Orders';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Customer Select
                Select::make('customer_id')
                    ->label('Customer')
                    ->options(Customer::all()->pluck('name', 'customer_id'))
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $customer = Customer::find($state);

                        if ($customer) {
                            $set('customer_name', $customer->name);
                            $set('phone_1', $customer->phone_1);
                            $set('phone_2', $customer->phone_2);
                            $set('email', $customer->email);
                        }
                    }),

                // Customer details (Readonly fields)
                Hidden::make('customer_id')
                    ->label('Customer ID')
                    ->default(fn ($get) => $get('customer_id')),

                TextInput::make('customer_name')
                    ->label('Customer Name')
                    ->disabled()
                    ->default(fn ($get) => $get('customer_name')),

                TextInput::make('phone_1')
                    ->label('Phone 1')
                    ->nullable(),

                TextInput::make('phone_2')
                    ->label('Phone 2')
                    ->nullable(),

                TextInput::make('email')
                    ->label('Email')
                    ->nullable(),

                // Order Name, Delivery Date, and Notes
                TextInput::make('name')
                    ->label('Order Name')
                    ->required(),

                DatePicker::make('wanted_delivery_date')
                    ->label('Wanted Delivery Date')
                    ->required(),

                Textarea::make('special_notes')
                    ->label('Special Notes')
                    ->nullable(),

                // Add added_by hidden field (tracks who added the order)
                Hidden::make('added_by')
                    ->default(fn () => auth()->user()->id)
                    ->required(),

                // Handle Customer Order Description (Line Items)
                \Filament\Forms\Components\Section::make('Order Items')
                    ->schema([
                        Repeater::make('order_items')
                            ->relationship('orderItems') // Assuming 'orderItems' is the relationship method on CustomerOrder model
                            ->schema([
                                TextInput::make('item_name')
                                    ->label('Item Name')
                                    ->required(),

                                // Main Item Fields (show when it's NOT a variation)
                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->required()
                                    ->numeric()
                                    ->visible(fn ($get) => !$get('is_variation')),

                                TextInput::make('price')
                                    ->label('Price')
                                    ->required()
                                    ->numeric()
                                    ->visible(fn ($get) => !$get('is_variation')),

                                TextInput::make('total')
                                    ->label('Total')
                                    ->required()
                                    ->numeric()
                                    ->default(function ($get) {
                                        return $get('quantity') * $get('price');
                                    })
                                    ->disabled() // Set as read-only
                                    ->visible(fn ($get) => !$get('is_variation')),

                                // Variation Toggle
                                Select::make('is_variation')
                                    ->label('Is Variation')
                                    ->options([0 => 'Normal Item', 1 => 'Variation Item'])
                                    ->default(0)
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state) {
                                            $set('quantity', 0); // Reset quantity
                                            $set('price', 0); // Reset price
                                            $set('total', 0); // Reset total
                                        }
                                    }),

                                    \Filament\Forms\Components\Repeater::make('sub_items')
                                    ->label('Variation Items')
                                    ->schema([
                                        // Define your table schema here
                                        TextInput::make('item_name')
                                            ->label('Item Name')
                                            ->required(),
                                
                                        TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->required(),
                                
                                        TextInput::make('price')
                                            ->label('Price')
                                            ->numeric()
                                            ->required(),
                                
                                        TextInput::make('total')
                                            ->label('Total')
                                            ->numeric()
                                            ->required()
                                            ->disabled() // Read-only
                                            ->default(function ($get) {
                                                return $get('quantity') * $get('price');
                                            }),
                                    ])
                                    ->columns(4) // This controls the number of columns in the layout
                                    ->visible(fn ($get) => $get('is_variation') == 1) // Only show when it's a variation
                                    ->reactive()
                                    ->addable() // Adds the ability to add new rows
                                    ->reorderable() // Allows rows to be reordered
                                    ->createItemButtonLabel('Add Variation Item'),
                                
                            ])
                            ->columns(3)
                            ->createItemButtonLabel('Add Order Item'),
                    ]),
            ]);
    }

    // Overriding the create method to handle saving order items and variations
    public static function create(array $data)
    {
        // Create the main customer order
        $customerOrder = CustomerOrder::create($data);

        // Process each order item
        foreach ($data['order_items'] as $itemData) {
            // Create the main order description
            $orderDescription = $customerOrder->orderDescriptions()->create([
                'item_name' => $itemData['item_name'],
                'variation_name' => $itemData['is_variation'] ? $itemData['item_name'] : null,
                'quantity' => $itemData['quantity'],
                'price' => $itemData['price'],
                'total' => $itemData['total'],
                'note' => $itemData['note'],
                'is_variation' => $itemData['is_variation'],
            ]);

            // If it's a variation, create the variation items
            if ($itemData['is_variation'] == 1 && isset($itemData['sub_items'])) {
                foreach ($itemData['sub_items'] as $subItemData) {
                    // Create the variation item
                    $orderDescription->variationItems()->create([
                        'item_name' => $subItemData['item_name'],
                        'quantity' => $subItemData['quantity'],
                        'price' => $subItemData['price'],
                        'total' => $subItemData['total'],
                    ]);
                }
            }
        }

        return $customerOrder;
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_id')->label('ID'),
                TextColumn::make('customer.name')->label('Customer Name'),
                TextColumn::make('name')->label('Order Name'),
                TextColumn::make('wanted_delivery_date')->label('Wanted Delivery Date'),
                BadgeColumn::make('status')->label('Status'),
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
            'index' => Pages\ListCustomerOrders::route('/'),
            'create' => Pages\CreateCustomerOrder::route('/create'),
            'edit' => Pages\EditCustomerOrder::route('/{record}/edit'),
        ];
    }
}
