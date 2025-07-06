<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdditionalOrderDiscountResource\Pages;
use App\Models\AdditionalOrderDiscount;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;

class AdditionalOrderDiscountResource extends Resource
{
    protected static ?string $model = AdditionalOrderDiscount::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Invoices';
    protected static ?int $navigationSort = 51;
    protected static ?string $navigationLabel = 'CO/SO Order Discounts';
    protected static ?string $label = 'CO/SO Order Discount';
    protected static ?string $pluralLabel = 'CO/SO Order Discounts';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Order Details')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('order_type')
                        ->label('Order Type')
                        ->required()
                        ->options([
                            'customer' => 'Customer Order',
                            'sample' => 'Sample Order',
                        ])
                        ->reactive()
                        ->afterStateUpdated(fn ($state, callable $set) => $set('order_id', null)),

                    Forms\Components\Select::make('order_id')
                        ->label('Order ID')
                        ->required()
                        ->searchable()
                        ->options(function (callable $get) {
                            $type = $get('order_type');

                            if ($type === 'customer') {
                                return \App\Models\CustomerOrder::whereNotIn('status', ['invoiced', 'closed'])
                                    ->get()
                                    ->mapWithKeys(fn ($order) => [
                                        $order->order_id => "ID: {$order->order_id} | Name: {$order->name} | Wanted Date: {$order->wanted_delivery_date}"
                                    ]);
                            }

                            if ($type === 'sample') {
                                return \App\Models\SampleOrder::whereNotIn('status', ['invoiced', 'closed'])
                                    ->get()
                                    ->mapWithKeys(fn ($order) => [
                                        $order->order_id => "ID: {$order->order_id} | Name: {$order->name} | Wanted Date: {$order->wanted_delivery_date}"
                                    ]);
                            }

                            return [];
                        })
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            $orderType = $get('order_type');
                            $orderId = ltrim($state, '0'); 

                            $order = null;

                            if ($orderType === 'sample') {
                                $order = \App\Models\SampleOrder::where('order_id', $orderId)
                                    ->whereNotIn('status', ['invoiced', 'closed'])
                                    ->first();
                            } elseif ($orderType === 'customer') {
                                $order = \App\Models\CustomerOrder::where('order_id', $orderId)
                                    ->whereNotIn('status', ['invoiced', 'closed'])
                                    ->first();
                            }

                            if (!$order) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Order Not Found or Invalid')
                                    ->body('The selected order is either invoiced, closed, or does not exist.')
                                    ->danger()
                                    ->send();

                                $set('customer_id', null);
                                $set('wanted_delivery_date', null);
                                $set('grand_total', null);
                                $set('remaining_balance_old', null);
                                return;
                            }

                            $set('customer_id', $order->customer_id ?? null);
                            $set('wanted_delivery_date', $order->wanted_delivery_date ?? null);
                            $set('grand_total', $order->grand_total ?? null);
                            $set('remaining_balance_old', $order->remaining_balance ?? null);
                        }),

                    Forms\Components\TextInput::make('customer_id')
                        ->label('Customer ID')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('wanted_delivery_date')
                        ->label('Wanted Delivery Date')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('grand_total')
                        ->label("Order's Grand Total")
                        ->numeric()
                        ->prefix('Rs.')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\TextInput::make('remaining_balance_old')
                        ->label('Old Remaining Balance (Current)')
                        ->numeric()
                        ->prefix('Rs.')
                        ->disabled()
                        ->dehydrated(false),
                ]),
            
            Section::make('Discount Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('amount')
                        ->required()
                        ->numeric()
                        ->prefix('Rs.')
                        ->helperText('Your discount amount will be added to the remaining balance of the order, You can deduct the amount in customer invoice payment option.'),

                    Forms\Components\TextInput::make('description')
                        ->required(),

                    Forms\Components\DatePicker::make('recorded_date')
                        ->label('Recorded Date')
                        ->default(today())
                        ->required()
                        ->disabled(function () {
                            return !auth()->user()?->can('backdate order discount') 
                                && !auth()->user()?->can('future order discount');
                        })
                        ->disabledDates(function () {
                            $today = today();
                            $canBackdate = auth()->user()?->can('backdate order discount');
                            $canFuturedate = auth()->user()?->can('future order discount');

                            return function (\Carbon\Carbon $date) use ($today, $canBackdate, $canFuturedate) {
                                if (!$canBackdate && $date->lt($today)) {
                                    return true;
                                }

                                if (!$canFuturedate && $date->gt($today)) {
                                    return true;
                                }

                                return false;
                            };
                        })
                        ->helperText(function () {
                            if (!auth()->user()?->can('backdate order discount') && !auth()->user()?->can('future order discount')) {
                                return 'You don’t have permission to select a different date. Today is auto-selected.';
                            }
                            return null;
                        }),

                    Forms\Components\Textarea::make('remarks')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('order_type')->sortable(),
            Tables\Columns\TextColumn::make('order_id')->sortable(),
            Tables\Columns\TextColumn::make('amount')->money('LKR'),
            Tables\Columns\TextColumn::make('description')->limit(30),
            Tables\Columns\TextColumn::make('recorded_date')->date(),
            Tables\Columns\TextColumn::make('created_by')->label('Created By'),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdditionalOrderDiscounts::route('/'),
            'create' => Pages\CreateAdditionalOrderDiscount::route('/create'),
            'edit' => Pages\EditAdditionalOrderDiscount::route('/{record}/edit'),
        ];
    }
}
