<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerAdvanceInvoiceResource\Pages;
use App\Models\CustomerAdvanceInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;

class CustomerAdvanceInvoiceResource extends Resource
{
    protected static ?string $model = CustomerAdvanceInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Invoices';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Order & Operation Details')
                        ->schema([
                            Section::make('Order Details')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('order_type')
                                                ->label('Order Type')
                                                ->options([
                                                    'customer_order' => 'Customer Order',
                                                    'sample_order' => 'Sample Order',
                                                ])
                                                ->required()
                                                ->reactive()
                                                ->disabled(fn ($get, $record) => $record !== null)
                                                ->dehydrated()
                                                ->afterStateUpdated(function ($state, $set) {
                                                    $set('order_id', null);
                                                    $set('customer_id', null);
                                                    $set('wanted_date', null);
                                                }),

                                            Select::make('order_id')
                                                ->label('Order')
                                                ->required()
                                                ->disabled(fn ($get, $record) => $record !== null)
                                                ->dehydrated()
                                                ->options(function ($get) {
                                                    $orderType = $get('order_type');
                                                    if ($orderType === 'customer_order') {
                                                        return \App\Models\CustomerOrder::pluck('name', 'order_id');
                                                    } elseif ($orderType === 'sample_order') {
                                                        return \App\Models\SampleOrder::pluck('name', 'order_id');
                                                    }
                                                    return [];
                                                })
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    $set('customer_id', null);
                                                    $set('wanted_date', null);

                                                    $orderType = $get('order_type');
                                                    if ($orderType && $state) {
                                                        if ($orderType === 'customer_order') {
                                                            $order = \App\Models\CustomerOrder::find($state);
                                                        } elseif ($orderType === 'sample_order') {
                                                            $order = \App\Models\SampleOrder::find($state);
                                                        }

                                                        if ($order) {
                                                            $set('customer_id', $order->customer_id ?? 'N/A');
                                                            $set('wanted_date', $order->wanted_delivery_date ?? 'N/A');
                                                        } else {
                                                            $set('customer_id', 'N/A');
                                                            $set('wanted_date', 'N/A');
                                                        }

                                                    } else {
                                                        $set('customer_id', 'N/A');
                                                        $set('wanted_date', 'N/A');
                                                    }
                                                }),

                                            TextInput::make('customer_id')
                                                ->label('Customer ID')
                                                ->disabled(),

                                            TextInput::make('wanted_date')
                                                ->label('Wanted Date')
                                                ->disabled(),
                                        ]),
                                ]),     
                        ]),

                    Tab::make('Order Variations')
                        ->schema([
                            Section::make('Order Variations Data')
                                ->schema([
                                    Repeater::make('order_variations')
                                        ->schema([
                                            TextInput::make('variation_id')->label('Variation ID')->disabled(),
                                            TextInput::make('variation_name')->label('Variation Name')->disabled(),
                                            TextInput::make('variation_quantity')->label('Quantity')->disabled(),
                                            TextInput::make('variation_price')->label('Price')->disabled(),
                                        ])
                                        ->columns(4)
                                        ->disabled(),
                                ]),
                        ]),
                ])
                ->columnspanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_type')->label('Order Type')->sortable(),
                TextColumn::make('order_id')->label('Order ID')->sortable(),
                TextColumn::make('wanted_date')->label('Wanted Date')->sortable(),
                TextColumn::make('customer_id')->label('Customer ID')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
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
            'index' => Pages\ListCustomerAdvanceInvoices::route('/'),
            'create' => Pages\CreateCustomerAdvanceInvoice::route('/create'),
        ];
    }
}