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
use Filament\Forms\Components\Tabs;
use Illuminate\Support\Carbon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationGroup = 'Orders';
    protected static ?string $navigationLabel = 'Purchase Orders';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Purchase Order')
                    ->tabs([
                        Tabs\Tab::make('Order Details')
                            ->schema([
                                Section::make('Order Information')
                                    ->schema([
                                        Select::make('provider_type')
                                            ->label('Provider Type')
                                            ->options([
                                                'supplier' => 'Supplier',
                                                'customer' => 'Customer',
                                            ])
                                            ->reactive()
                                            ->required()
                                            ->disabled(fn (string $operation): bool => $operation === 'edit'),

                                        Select::make('provider_id')
                                            ->label('Provider')
                                            ->options(function ($get) {
                                                if ($get('provider_type') === 'supplier') {
                                                    return \App\Models\Supplier::all()
                                                        ->mapWithKeys(fn ($supplier) => [
                                                            $supplier->supplier_id => "Supplier ID - {$supplier->supplier_id} | Name - {$supplier->name}"
                                                        ])
                                                        ->toArray();
                                                } elseif ($get('provider_type') === 'customer') {
                                                    return \App\Models\Customer::all()
                                                        ->mapWithKeys(fn ($customer) => [
                                                            $customer->customer_id => "Customer ID - {$customer->customer_id} | Name - {$customer->name}"
                                                        ])
                                                        ->toArray();
                                                }
                                                return [];
                                            })
                                            ->searchable()
                                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                if ($get('provider_type') === 'supplier') {
                                                    $supplier = \App\Models\Supplier::find($state);
                                                    $set('provider_name', $supplier?->name);
                                                    $set('provider_email', $supplier?->email);
                                                    $set('provider_phone', $supplier?->phone_1);
                                                } elseif ($get('provider_type') === 'customer') {
                                                    $customer = \App\Models\Customer::find($state);
                                                    $set('provider_name', $customer?->name);
                                                    $set('provider_email', $customer?->email);
                                                    $set('provider_phone', $customer?->phone_1);
                                                } else {
                                                    $set('provider_name', null);
                                                    $set('provider_email', null);
                                                    $set('provider_phone', null);
                                                }
                                            }),

                                        TextInput::make('provider_name')
                                            ->label('Name')
                                            ->disabled(),

                                        TextInput::make('provider_email')
                                            ->label('Email')
                                            ->disabled(),

                                        TextInput::make('provider_phone')
                                            ->label('Phone')
                                            ->disabled(),

                                        DatePicker::make('wanted_date')
                                            ->label('Wanted Delivery Date')
                                            ->required()
                                            ->minDate(now()),

                                        Textarea::make('special_note')
                                            ->label('Special Note')
                                            ->nullable()
                                            ->columnSpan(2), 

                                        Hidden::make('user_id')
                                            ->default(fn () => auth()->id()),
                                    ])
                                    ->columns(2)
                                ]),

                        Tabs\Tab::make('Order Items')
                            ->schema([
                                Section::make('Items')
                                    ->schema([
                                        Repeater::make('items')
                                            ->relationship('items')
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        Select::make('inventory_item_id')
                                                            ->label('Item')
                                                            ->relationship('inventoryItem', 'name')
                                                            ->searchable()
                                                            ->preload()
                                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->id} | Item Code - {$record->item_code} | Name - {$record->name}")
                                                            ->required(),

                                                        TextInput::make('quantity')
                                                            ->label('Quantity')
                                                            ->numeric()
                                                            ->required()
                                                            ->afterStateUpdated(function ($state, callable $set) {
                                                                $set('remaining_quantity', $state);
                                                                $set('arrived_quantity', 0);
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
                                            ->minItems(1)
                                            ->createItemButtonLabel('Add Order Item')
                                            ->columnSpan('full'),
                                    ])
                                    ->columnSpan('full'),
                            ])
                            ->columns(1),
                    ])
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Order ID')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('provider_type')->label('Provider Type'),
                TextColumn::make('provider_id')->label('Provider ID')->searchable(),
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
                            TextColumn::make('created_by')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                            TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                            TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                            TextColumn::make('updated_at')->label('Updated At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                        ]
                        : []
                        ),
            ])
            ->filters([
                SelectFilter::make('provider_type')
                    ->label('Provider Type')
                    ->options([
                        'supplier' => 'Supplier',
                        'customer' => 'Customer',
                    ])
                    ->placeholder('All Types'),

                Filter::make('wanted_date')
                    ->label('Wanted Delivery Date')
                    ->form([
                        DatePicker::make('date')
                            ->label('Wanted Delivery Date')
                            ->closeOnDateSelection(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when($data['date'], fn ($q, $date) =>
                            $q->whereDate('wanted_date', $date)
                        );
                    }),
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
                        $record->status === 'planned'
                    ),
                
            ])
            ->defaultSort('id', 'desc')
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