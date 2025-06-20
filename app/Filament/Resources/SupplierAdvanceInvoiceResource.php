<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierAdvanceInvoiceResource\Pages;
use App\Models\SupplierAdvanceInvoice;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;


class SupplierAdvanceInvoiceResource extends Resource
{
    protected static ?string $model = SupplierAdvanceInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Invoices';
    protected static ?string $label = 'PO Advance Invoice';
    protected static ?string $pluralLabel = 'PO Advance Invoices';
    protected static ?string $navigationLabel = 'PO Advance Invoices';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Purchase Order Details')
                        ->schema([
                            Section::make('Purchase Order Information')
                                ->columns(2)
                                ->schema([
                                    Select::make('purchase_order_id')
                                        ->label('Purchase Order')
                                        ->required()
                                        ->dehydrated()
                                        ->disabled(fn (?string $context) => $context === 'edit')
                                        ->searchable()
                                        ->options(function () {
                                            return \App\Models\PurchaseOrder::all()->mapWithKeys(function ($order) {
                                                return [
                                                    $order->id => "ID:{$order->id} | Created at:{$order->created_at->format('Y-m-d')} | Provider:{$order->provider_name}",
                                                ];
                                            });
                                        })                        
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, $set) {
                                            $purchaseOrder = PurchaseOrder::find($state);

                                            if ($purchaseOrder) {
                                                $set('provider_type', $purchaseOrder->provider_type ?? 'N/A');
                                                $set('provider_email', $purchaseOrder->provider_email ?? 'N/A');
                                                $set('wanted_date', $purchaseOrder->wanted_date ?? 'N/A');
                                                $set('status', $purchaseOrder->status ?? 'N/A');

                                                if ($purchaseOrder->items) {
                                                    $set('purchase_order_items', $purchaseOrder->items->toArray());
                                                } else {
                                                    $set('purchase_order_items', []);
                                                }
                                            } else {
                                                $set('provider_type', 'N/A');
                                                $set('provider_email', 'N/A');
                                                $set('wanted_date', 'N/A');
                                                $set('status', 'N/A');
                                            }
                                        }),

                                    TextInput::make('provider_type')
                                        ->label('Provider Type')
                                        ->disabled(),

                                    TextInput::make('provider_email')
                                        ->label('Provider Email')
                                        ->disabled(),

                                    TextInput::make('wanted_date')
                                        ->label('Wanted Date')
                                        ->disabled(),
                                    
                                    TextInput::make('status')
                                        ->label('Status')
                                        ->disabled(),
                                ]),

                            Section::make('Purchase Order Items')
                                ->schema([
                                    Repeater::make('purchase_order_items')
                                        ->columns(5)
                                        ->schema([
                                            TextInput::make('inventory_item_id')->label('Inventory Item ID')->disabled(),
                                            TextInput::make('quantity')->label('Quantity')->disabled(),
                                            TextInput::make('price')->label('Price')->disabled(),
                                            TextInput::make('arrived_quantity')->label('Arrived Quantity')->disabled(),
                                            TextInput::make('total_sale')->label('Total Sale')->disabled(),
                                        ])
                                        ->disabled(),          
                                        
                                        Placeholder::make('grand_total')
                                            ->label('Grand Total')
                                            ->content(function (Get $get) {
                                                $items = $get('purchase_order_items') ?? [];
                                                $sum = collect($items)->sum('total_sale');
                                                return 'Rs. ' . number_format((float) $sum, 2);
                                            }),

                                        Hidden::make('grand_total')
                                        ->dehydrated(),
                                ]),
                        ]),

                    Tabs\Tab::make('Payment for Purchase Order Items')
                        ->schema([
                            Section::make('Grand Total of Purchase Order Items')
                                ->schema([
                                    Placeholder::make('grand_total')
                                        ->label('Grand Total')
                                        ->disabled()
                                        ->content(function (Get $get) {
                                            $items = $get('purchase_order_items') ?? [];
                                            $sum = collect($items)->sum('total_sale');
                                            return 'Rs. ' . number_format((float) $sum, 2);
                                        }),
                                ]),

                            Section::make('Make the Payment')
                                ->columns(3)
                                ->schema([
                                    Select::make('payment_type')
                                        ->label('Payment Type')
                                        ->dehydrated()
                                        ->options([
                                            'fixed' => 'Fixed Amount',
                                            'percentage' => 'Percentage',
                                        ])
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set) {
                                            $set('payment_amount', null);
                                            $set('payment_percentage', null);
                                            $set('calculated_payment', null);
                                        }),

                                    // Amount input for fixed payment
                                    TextInput::make('fix_payment_amount')
                                        ->label('Enter Amount')
                                        ->dehydrated()
                                        ->suffix('Rs.')
                                        ->live()
                                        ->required(fn ($get) => $get('payment_type') === 'fixed')
                                        ->visible(fn ($get) => $get('payment_type') === 'fixed')
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $grandTotal = collect($get('purchase_order_items') ?? [])->sum('total_sale');

                                            if ($state > $grandTotal) {
                                                // Clear the field and calculated payment
                                                $set('fix_payment_amount', null);
                                                $set('calculated_payment', null);

                                                // Show notification
                                                Notification::make()
                                                    ->title('Invalid Payment')
                                                    ->body('The entered amount exceeds the Grand Total.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            // Set calculated payment to the entered amount
                                            $set('calculated_payment', $state);
                                        }),

                                    // Percentage input for percentage-based payment
                                    TextInput::make('payment_percentage')
                                        ->label('Enter Percentage')
                                        ->dehydrated()
                                        ->suffix('%')
                                        ->required(fn ($get) => $get('payment_type') === 'percentage')
                                        ->visible(fn ($get) => $get('payment_type') === 'percentage')
                                        ->live()
                                        ->afterStateUpdated(function ($state, $set, $get) {
                                            $grandTotal = collect($get('purchase_order_items') ?? [])->sum('total_sale');
                                            $calculated = $grandTotal * ($state / 100);

                                            if ($calculated > $grandTotal) {
                                                $set('payment_percentage', null);
                                                $set('percent_calculated_payment', null);

                                                Notification::make()
                                                    ->title('Invalid Percentage')
                                                    ->body('Calculated payment exceeds the Grand Total.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            $set('percent_calculated_payment', $calculated); 
                                        }),
                                    // Common display field for calculated amount
                                    TextInput::make('percent_calculated_payment')
                                        ->label('Calculated Payment')
                                        ->suffix('Rs.')
                                        ->disabled()
                                        ->live()
                                        ->dehydrated()
                                        ->visible(fn ($get) => $get('payment_type') === 'percentage')
                                        ->default(function ($get) {
                                            $grandTotal = collect($get('purchase_order_items') ?? [])->sum('total_sale');
                                            $paymentPercentage = $get('payment_percentage');

                                            $calculated = $paymentPercentage ? $grandTotal * ($paymentPercentage / 100) : null;

                                            return $calculated && $calculated <= $grandTotal ? $calculated : null;
                                        }),

                                ]),
                            ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Sup ADV. Invoice ID')->sortable()->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('purchase_order_id')->label('Purchase Order ID')->sortable()->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('paid_amount')->label('Paid Amount')->sortable()
                    ->formatStateUsing(fn ($state) => 'Rs. ' . number_format((float) $state, 2)),
                TextColumn::make('remaining_amount')->label('Remaining Amount')->sortable()
                    ->formatStateUsing(fn ($state) => 'Rs. ' . number_format((float) $state, 2)),
                TextColumn::make('status')->label('Status')->sortable(),
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
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('viewPdf')
                ->label('View PDF')
                ->color('success')
                ->icon('heroicon-o-eye')
                ->url(fn (SupplierAdvanceInvoice $record): string => route('supplier-advance-invoices.pdf', $record))
                ->openUrlInNewTab(),
                
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->status !== 'pending')
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
            'index' => Pages\ListSupplierAdvanceInvoices::route('/'),
            'create' => Pages\CreateSupplierAdvanceInvoice::route('/create'),
        ];
    }
}