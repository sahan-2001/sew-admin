<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SampleOrderResource\Pages;
use App\Models\SampleOrder;
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
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Carbon;
use Filament\Tables\Filters\SelectFilter;

class SampleOrderResource extends Resource
{
    protected static ?string $model = SampleOrder::class;

    protected static ?string $navigationGroup = 'Orders';
    protected static ?string $navigationLabel = 'Sample Customer Orders';
    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('view sample orders') ?? false;
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Customer Details')
                    ->columns(2)
                    ->schema([
                        // Customer Selection
                        Select::make('customer_id')
                            ->label('Customer')
                            ->options(fn () =>
                                \App\Models\Customer::all()
                                    ->mapWithKeys(fn ($customer) => [
                                        $customer->customer_id => "id={$customer->customer_id} | name={$customer->name}"
                                    ])
                                    ->toArray()
                            )
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                $customer = \App\Models\Customer::find($state);

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
                                ->nullable()
                                ->disabled(),

                            TextInput::make('phone_2')
                                ->label('Phone 2')
                                ->nullable()
                                ->disabled(),

                            TextInput::make('email')
                                ->label('Email')
                                ->nullable()
                                ->disabled(),
                    ]),

                // Order Details
                Section::make('Order Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Order Name')
                            ->required(),

                        DatePicker::make('wanted_delivery_date')
                            ->label('Wanted Delivery Date')
                            ->required()
                            ->minDate(Carbon::today()),

                        Textarea::make('special_notes')
                            ->label('Special Notes')
                            ->nullable(),
                    ]),

                Hidden::make('added_by')
                    ->default(fn () => auth()->user()->id)
                    ->required(),

                Section::make('Order Items')
                    ->schema([
                        Repeater::make('sample_order_items')
                            ->relationship('items')
                            ->schema([
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

                                Repeater::make('sample_order_variations')
                                    ->label('Variation Items')
                                    ->relationship('variations')
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
                TextColumn::make('order_id')
                    ->label('Order ID')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('customer.name')->label('Customer Name')->searchable(),
                TextColumn::make('name')->label('Order Name')->searchable(),
                TextColumn::make('wanted_delivery_date')->label('Wanted Delivery Date'),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => fn ($state): bool => $state === 'planned',
                        'blue' => fn ($state): bool => $state === 'in_progress',
                        'green' => fn ($state): bool => $state === 'completed',
                    ])
                    ->getStateUsing(fn ($record) => $record->status),
                TextColumn::make('grand_total')->label('Total Sale')
                    ->formatStateUsing(fn ($state) => 'Rs. ' . number_format((float) $state, 2)),
                TextColumn::make('remaining_balance')->label('Remaining Balance')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                ...(
                Auth::user()->can('view audit columns')
                    ? [
                        TextColumn::make('created_by')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('updated_at')->label('Updated At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                    ]
                    : []
                    ),
            ])
            ->filters([
                // Filter by Status
                SelectFilter::make('status')
                    ->label('Order Status')
                    ->options([
                        'planned' => 'Planned',
                        'material released' => 'Material Released',
                        'released' => 'Released',
                        'cut' => 'Cut',
                        'started' => 'Started',
                        'completed' => 'Completed',
                        'delivered' => 'Delivered',
                        'accepted' => 'Accepted',
                        'invoiced' => 'Invoiced',
                        'closed' => 'Closed',
                        'rejected' => 'Rejected',
                    ])
                    ->searchable(),

                // Filter by Created Date
                Filter::make('created_at')
                    ->label('Created Date')
                    ->form([
                        DatePicker::make('date')
                            ->label('Created Date')
                            ->maxDate(today())
                            ->closeOnDateSelection()
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['date'], fn ($q, $date) =>
                                $q->whereDate('created_at', $date)
                            );
                    }),
                Filter::make('wanted_delivery_date')
                    ->label('Wanted Delivery Date')
                    ->form([
                        DatePicker::make('date')
                            ->label('Wanted Delivery Date')
                            ->closeOnDateSelection()
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['date'], fn ($q, $date) =>
                                $q->whereDate('wanted_delivery_date', $date)
                            );
                    }),
            ])
            ->actions([
                EditAction::make()
                ->visible(fn ($record) => 
                    auth()->user()->can('edit sample orders') &&
                    in_array($record->status, ['planned', 'released'])
                ),
                DeleteAction::make()
                ->visible(fn ($record) => 
                    auth()->user()->can('delete sample orders') &&
                    $record->status === 'planned'
                ),
                Action::make('handle')
                        ->label('Handle')
                        ->url(fn ($record) => SampleOrderResource::getUrl('handle', ['record' => $record]))
                        ->openUrlInNewTab(false),
            ])
        ->defaultSort('order_id', 'desc') 
        ->recordUrl(null);
            
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSampleOrders::route('/'),
            'create' => Pages\CreateSampleOrder::route('/create'),
            'edit' => Pages\EditSampleOrder::route('/{record}/edit'),
            'handle' => Pages\HandleSampleOrder::route('/{record}/handle'),
        ];
    }
}
