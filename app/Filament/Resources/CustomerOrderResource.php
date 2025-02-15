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
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Actions\Action;

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
                // Customer Selection
                Select::make('customer_id')
                    ->label('Customer')
                    ->options(fn () => Customer::pluck('name', 'customer_id')->toArray()) // Lazy loading for performance
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

                // Customer Details (Readonly Fields)
                TextInput::make('customer_name')
                    ->label('Customer Name')
                    ->disabled(),

                TextInput::make('phone_1')
                    ->label('Phone 1')
                    ->nullable(),

                TextInput::make('phone_2')
                    ->label('Phone 2')
                    ->nullable(),

                TextInput::make('email')
                    ->label('Email')
                    ->nullable(),

                // Order Details
                TextInput::make('name')
                    ->label('Order Name')
                    ->required(),

                DatePicker::make('wanted_delivery_date')
                    ->label('Wanted Delivery Date')
                    ->required(),

                Textarea::make('special_notes')
                    ->label('Special Notes')
                    ->nullable(),

                Hidden::make('added_by')
                    ->default(fn () => auth()->user()->id)
                    ->required(),

                // Order Items Section
                Section::make('Order Items')
                    ->schema([
                        Repeater::make('order_items')
                            ->relationship('orderItems') // Ensure this relationship is defined in the CustomerOrder model
                            ->schema([
                                // Row 1: Item Name and Is Variation Toggle
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('item_name')
                                            ->label('Item Name')
                                            ->required(),

                                        Toggle::make('is_variation')
                                            ->label('Is Variation')
                                            ->default(false)
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $set, $state) {
                                                if ($state) {
                                                    $set('quantity', 0);
                                                    $set('price', 0);
                                                    $set('total', 0);
                                                }
                                            }),
                                    ]),

                                // Row 2: Quantity, Price, and Total (For Non-Variation Items)
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $set, $state, $get) {
                                                $set('total', $state * $get('price'));
                                            })
                                            ->visible(fn ($get) => !$get('is_variation')),

                                        TextInput::make('price')
                                            ->label('Price')
                                            ->numeric()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $set, $state, $get) {
                                                $set('total', $state * $get('quantity'));
                                            })
                                            ->visible(fn ($get) => !$get('is_variation')),

                                        TextInput::make('total')
                                            ->label('Total')
                                            ->numeric()
                                            ->disabled()
                                            ->default(fn ($get) => $get('quantity') * $get('price'))
                                            ->visible(fn ($get) => !$get('is_variation')),
                                    ]),

                                // Variation Items Table (For Variation Items)
                                Repeater::make('sub_items')
                                    ->label('Variation Items')
                                    ->relationship('variationItems') // Ensure this relationship is defined in the CustomerOrderDescription model
                                    ->schema([
                                        TextInput::make('variation_name')
                                            ->label('Variation Name')
                                            ->required(),

                                        TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->required()
                                            ->reactive(),

                                        TextInput::make('price')
                                            ->label('Price')
                                            ->numeric()
                                            ->required()
                                            ->reactive(),

                                        TextInput::make('total')
                                            ->label('Total')
                                            ->numeric()
                                            ->disabled()
                                            ->default(fn ($get) => $get('quantity') * $get('price')),
                                    ])
                                    ->columns(4)
                                    ->visible(fn ($get) => $get('is_variation') == true)
                                    ->addable()
                                    ->reorderable()
                                    ->createItemButtonLabel('Add Variation Item'),
                            ])
                            ->columns(1)
                            ->createItemButtonLabel('Add Order Item'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_id')->label('ID'),
                TextColumn::make('customer.name')->label('Customer Name'),
                TextColumn::make('name')->label('Order Name'),
                TextColumn::make('wanted_delivery_date')->label('Wanted Delivery Date'),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => fn ($state): bool => $state === 'planned',
                        'blue' => fn ($state): bool => $state === 'in_progress',
                        'green' => fn ($state): bool => $state === 'completed',
                    ])
                    ->getStateUsing(fn ($record) => $record->status),
                TextColumn::make('created_at')->label('Created Date')->dateTime(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('view_pdf')
                    ->label('View PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => route('customer-orders.pdf', $record))
                    ->openUrlInNewTab(),
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