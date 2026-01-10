<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestForQuotationResource\Pages;
use App\Models\RequestForQuotation;
use App\Models\InventoryItem;
use App\Models\Supplier;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Forms\Components\{
    TextInput,
    Select,
    DatePicker,
    Textarea,
    Repeater,
    Hidden,
    Section,
    Grid,
    Tabs
};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\{EditAction, DeleteAction, Action};
use Illuminate\Support\Facades\Auth;

class RequestForQuotationResource extends Resource
{
    protected static ?string $model = RequestForQuotation::class;

    protected static ?string $navigationGroup = 'Orders';
    protected static ?string $navigationLabel = 'Request for Purchase Quotations';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('view purchase quotations') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Purchase Quotation')
                ->tabs([
                    Tabs\Tab::make('Quotation Details')
                        ->schema([
                            Section::make('Supplier Information')
                                ->columns(2)
                                ->schema([
                                    DatePicker::make('quotation_date')
                                        ->label('Quotation Date')
                                        ->default(now())
                                        ->disabled(),

                                    DatePicker::make('valid_until')
                                        ->label('Valid Until')
                                        ->minDate(now())
                                        ->required(),

                                    Section::make('Supplier')
                                        ->columns(3)
                                        ->schema([
                                            Select::make('supplier_id')
                                                ->label('Supplier')
                                                ->options(
                                                    Supplier::query()
                                                        ->pluck('name', 'supplier_id')
                                                )
                                                ->searchable()
                                                ->preload()
                                                ->live()
                                                ->getOptionLabelUsing(
                                                    fn ($value): ?string =>
                                                        Supplier::find($value)
                                                            ?->tap(fn ($s) => "{$s->supplier_id} | {$s->name}")
                                                )
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    $supplier = Supplier::find($state);

                                                    if ($supplier) {
                                                        $set('supplier_name', $supplier->name);
                                                        $set('supplier_email', $supplier->email);
                                                        $set('supplier_phone', $supplier->phone_1);
                                                    }
                                                }),

                                            TextInput::make('supplier_name')->disabled(),
                                            TextInput::make('supplier_email')->disabled(),
                                        ]),
                                ]),

                            Section::make('Quotation Meta')
                                ->columns(2)
                                ->schema([
                                    DatePicker::make('wanted_delivery_date')
                                        ->label('Expected Delivery Date')
                                        ->minDate(now()),

                                    Textarea::make('special_note')
                                        ->label('Remarks')
                                        ->columnSpan(2),

                                    Hidden::make('status')->default('draft'),
                                ]),
                        ]),

                    Tabs\Tab::make('Quotation Items')
                        ->schema([
                            Section::make('Items')
                                ->schema([
                                    Repeater::make('items')
                                        ->relationship('items')
                                        ->schema([
                                            Grid::make(3)->schema([
                                                Select::make('inventory_item_id')
                                                    ->label('Item')
                                                    ->relationship('inventoryItem', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->reactive()
                                                    ->columnSpan(2)
                                                    ->getOptionLabelFromRecordUsing(
                                                        fn ($record) =>
                                                            "{$record->id} | Code - {$record->item_code} | {$record->name}"
                                                    ),

                                                TextInput::make('quantity')
                                                    ->numeric()
                                                    ->required()
                                                    ->reactive()
                                                    ->afterStateUpdated(
                                                        fn ($state, $set, $get) =>
                                                            self::recalculate($set, $get)
                                                    ),

                                                TextInput::make('price')
                                                    ->numeric()
                                                    ->required()
                                                    ->reactive()
                                                    ->prefix('Rs.')
                                                    ->afterStateUpdated(
                                                        fn ($state, $set, $get) =>
                                                            self::recalculate($set, $get)
                                                    ),

                                                TextInput::make('item_subtotal')
                                                    ->prefix('Rs.')
                                                    ->disabled()
                                                    ->dehydrated(true),

                                                Hidden::make('remaining_quantity')
                                                    ->default(fn ($get) => $get('quantity')),

                                                Hidden::make('arrived_quantity')
                                                    ->default(0),
                                            ]),
                                        ])
                                        ->minItems(1)
                                        ->createItemButtonLabel('Add Item')
                                        ->columnSpanFull()
                                        ->reactive()
                                        ->afterStateUpdated(
                                            fn ($state, $set, $get) =>
                                                self::calculateSummary($set, $get)
                                        ),
                                ]),

                            Section::make('Order Summary')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('items_sub_total_sum')
                                            ->label('Total Quotation Amount')
                                            ->prefix('Rs.')
                                            ->disabled()
                                            ->default(0.00),

                                        \Filament\Forms\Components\Actions::make([
                                            \Filament\Forms\Components\Actions\Action::make('refreshSummary')
                                                ->icon('heroicon-o-arrow-path')
                                                ->color('gray')
                                                ->action(
                                                    fn (callable $set, callable $get) =>
                                                        self::calculateSummary($set, $get)
                                                ),
                                        ])->alignEnd(),
                                    ]),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    protected static function recalculate(callable $set, callable $get): void
    {
        $qty   = (float) $get('quantity');
        $price = (float) $get('price');

        $subTotal = $qty * $price;

        $set('item_subtotal', round($subTotal, 2));

        self::calculateSummary($set, $get);
    }

    protected static function calculateSummary(callable $set, callable $get): void
    {
        $items = $get('items') ?? [];

        $subTotalSum = collect($items)
            ->sum(fn ($i) => (float) ($i['item_subtotal'] ?? 0));

        $set('items_sub_total_sum', round($subTotalSum, 2));
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['order_subtotal'] = $data['items_sub_total_sum'] ?? 0;
        return $data;
    }

    protected static function mutateFormDataBeforeSave(array $data): array
    {
        return self::mutateFormDataBeforeCreate($data);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('RFQ No')->sortable(),
                TextColumn::make('supplier.name')->label('Supplier')->searchable(),
                TextColumn::make('order_subtotal')->money('LKR'),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'draft' => 'gray',
                        'sent' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    ]),
            ])
            ->actions([
                Action::make('handle')
                    ->label('Handle RFQ')
                    ->icon('heroicon-o-cog')
                    ->color('primary')
                    ->url(fn (RequestForQuotation $record) =>
                        RequestForQuotationResource::getUrl('handle', ['record' => $record])
                ),

                EditAction::make()
                    ->visible(fn ($record) =>
                        $record->status === 'draft' &&
                        auth()->user()->can('edit purchase quotations')
                    ),

                DeleteAction::make()
                    ->visible(fn ($record) =>
                        $record->status === 'draft' &&
                        auth()->user()->can('delete purchase quotations')
                    ),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRequestForQuotations::route('/'),
            'create' => Pages\CreateRequestForQuotation::route('/create'),
            'edit'   => Pages\EditRequestForQuotation::route('/{record}/edit'),
            'handle' => Pages\HandleRequestForQuotation::route('/{record}/handle'),
        ];
    }
}
