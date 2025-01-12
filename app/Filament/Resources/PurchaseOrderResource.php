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
use Filament\Navigation\NavigationItem;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationGroup = 'Traders Management';

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
                            }
                        } elseif ($get('provider_type') === 'customer') {
                            $customer = Customer::find($state);
                            if ($customer) {
                                $set('provider_name', $customer->name);
                                $set('provider_email', $customer->email);
                                $set('provider_phone', $customer->phone_1);
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
                // Break section for inventory items
                \Filament\Forms\Components\Section::make('Select Inventory Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Select::make('inventory_item_id')
                                    ->label('Inventory Item')
                                    ->options(fn () => InventoryItem::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required(),
                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->required(),
                                TextInput::make('price')
                                    ->label('Price')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->createItemButtonLabel('Add Item'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('id')->label('ID'),
                \Filament\Tables\Columns\TextColumn::make('provider_type')->label('Provider Type'),
                \Filament\Tables\Columns\TextColumn::make('provider_id')->label('Provider ID'),
                \Filament\Tables\Columns\TextColumn::make('provider_name')->label('Provider Name'),
                \Filament\Tables\Columns\TextColumn::make('provider_email')->label('Provider Email'),
                \Filament\Tables\Columns\TextColumn::make('provider_phone')->label('Provider Phone'),
                \Filament\Tables\Columns\TextColumn::make('wanted_date')->label('Wanted Date'),
                \Filament\Tables\Columns\TextColumn::make('special_note')->label('Special Note')->limit(50),
                \Filament\Tables\Columns\TextColumn::make('created_at')->label('Created At')->dateTime(),
                \Filament\Tables\Columns\TextColumn::make('updated_at')->label('Updated At')->dateTime(),
            ])
            ->filters([
                // Define table filters here
            ])
            ->actions([
                Action::make('pdf')
                    ->label('Print PDF')
                    ->url(fn ($record) => route('purchase-orders.pdf', $record))
                    ->visible(fn ($record) => auth()->user()->can('view purchase orders')),
                ViewAction::make()
                    ->visible(fn ($record) => auth()->user()->can('view purchase orders')),
                EditAction::make()
                    ->visible(fn ($record) => auth()->user()->can('edit purchase orders')),
                DeleteAction::make()
                    ->visible(fn ($record) => auth()->user()->can('delete purchase orders')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make()
                ->label(static::$navigationLabel)
                ->icon(static::$navigationIcon)
                ->group(static::$navigationGroup)
                ->url(static::getUrl('index'))
                ->visible(fn () => auth()->user()->can('view purchase orders')),
        ];
    }
}