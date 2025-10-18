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
use Illuminate\Database\Eloquent\Builder; 
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Layout;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;

class AdditionalOrderDiscountResource extends Resource
{
    protected static ?string $model = AdditionalOrderDiscount::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'CO/ SO Invoices';
    protected static ?string $navigationLabel = 'CO/SO Order Discounts';
    protected static ?string $label = 'CO/SO Order Discount';
    protected static ?string $pluralLabel = 'CO/SO Order Discounts';
    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('view order discount') ?? false;
    }
    
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
            Tables\Columns\TextColumn::make('id')->label('Record ID')->sortable()->searchable()
                ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
            Tables\Columns\TextColumn::make('order_type')->sortable(),
            Tables\Columns\TextColumn::make('order_id')->sortable()->searchable()
                ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
            Tables\Columns\TextColumn::make('amount')->money('LKR'),
            Tables\Columns\TextColumn::make('recorded_date')->date(),
            Tables\Columns\TextColumn::make('status'),
            ...(
                Auth::user()->can('view audit columns')
                    ? [
                        Tables\Columns\TextColumn::make('created_by')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        Tables\Columns\TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        Tables\Columns\TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                        Tables\Columns\TextColumn::make('updated_at')->label('Updated At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                    ]
                    : []
                    ),
        ])
        ->filters([
            Filter::make('recorded_date')
                ->label('Recorded Date')
                ->form([
                    Forms\Components\DatePicker::make('date')->label('Recorded On'),
                ])
                ->query(function ($query, array $data) {
                    return $query->when($data['date'], fn ($q) => $q->whereDate('recorded_date', $data['date']));
                }),

            Filter::make('amount_range')
                ->label('Amount Range')
                ->form([
                    Forms\Components\TextInput::make('min')->numeric()->label('Min Amount'),
                    Forms\Components\TextInput::make('max')->numeric()->label('Max Amount'),
                ])
                ->query(function ($query, array $data) {
                    return $query
                        ->when($data['min'], fn ($q) => $q->where('amount', '>=', $data['min']))
                        ->when($data['max'], fn ($q) => $q->where('amount', '<=', $data['max']));
                }),

            SelectFilter::make('order_type')
                ->options([
                    'customer' => 'Customer',
                    'sample' => 'Sample',
                ])
                ->label('Order Type'),
        ])
        ->actions([
            Tables\Actions\EditAction::make()
                ->hidden(fn ($record) => $record->status === 'closed' || $record->status === 'approved')
                ->visible(fn () => auth()->user()?->can('edit order discount') ?? false),

            Tables\Actions\DeleteAction::make()
                ->hidden(fn ($record) => $record->status === 'closed')
                ->visible(fn () => auth()->user()?->can('delete order discount') ?? false),
        ])
        ->bulkActions([
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customerOrder', 'sampleOrder']);
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
