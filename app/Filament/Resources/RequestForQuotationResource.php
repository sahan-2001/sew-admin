<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestForQuotationResource\Pages;
use App\Models\RequestForQuotation;
use App\Models\InventoryItem;
use App\Models\Supplier;
use App\Models\Currency;
use App\Models\PaymentTerm;
use App\Models\DeliveryTerm;
use App\Models\DeliveryMethod;
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
        return Auth::user()?->can('View Request For Quotations') ?? false;
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
                                                        ->get()
                                                        ->mapWithKeys(fn ($s) => [
                                                            $s->supplier_id => "{$s->supplier_id} | {$s->name}"
                                                        ])
                                                        ->toArray()
                                                )
                                                ->searchable()
                                                ->preload()
                                                ->live()
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    $supplier = Supplier::where('supplier_id', $state)->first();

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
                                    Select::make('currency_code_id')
                                        ->label('Currency')
                                        ->options(
                                            fn () => \App\Models\Currency::where('is_active', true)
                                                ->get()
                                                ->mapWithKeys(fn ($c) => [
                                                    $c->id => "{$c->code} | {$c->name}"
                                                ])
                                                ->toArray()
                                        )
                                        ->searchable()
                                        ->preload(),

                                    Select::make('payment_term_id')
                                        ->label('Expected Payment Terms')
                                        ->options(
                                            fn () => \App\Models\PaymentTerm::get()
                                                ->mapWithKeys(fn ($p) => [
                                                    $p->id => "{$p->name} | {$p->description}"
                                                ])
                                                ->toArray()
                                        )
                                        ->searchable()
                                        ->preload(),

                                    Select::make('delivery_term_id')
                                        ->label('Expected Delivery Terms')
                                        ->options(
                                            fn () => \App\Models\DeliveryTerm::get()
                                                ->mapWithKeys(fn ($d) => [
                                                    $d->id => "{$d->name} | {$d->description}"
                                                ])
                                                ->toArray()
                                        )
                                        ->searchable()
                                        ->preload(),

                                    Select::make('delivery_method_id')
                                        ->label('Expected Delivery Method')
                                        ->options(
                                            fn () => \App\Models\DeliveryMethod::get()
                                                ->mapWithKeys(fn ($m) => [
                                                    $m->id => "{$m->name} | {$m->description}"
                                                ])
                                                ->toArray()
                                        )
                                        ->searchable()
                                        ->preload(),
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
                                                    ->reactive(),
                                            ]),
                                        ])
                                        ->minItems(1)
                                        ->createItemButtonLabel('Add Item')
                                        ->columnSpanFull(),
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
                TextColumn::make('id')
                    ->label('RFQ No')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('supplier.name')->label('Supplier')->searchable(),
                TextColumn::make('currency_code_id')->label('Currency')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'draft' => 'gray',
                        'sent' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'closed' => 'secondary',
                    ]),
            ])
            ->actions([
                Action::make('handle')
                    ->label('Handle RFQ')
                    ->icon('heroicon-o-cog')
                    ->color('primary')
                    ->visible(auth()->user()->can('Handle Request For Quotations'))
                    ->url(fn (RequestForQuotation $record) =>
                        RequestForQuotationResource::getUrl('handle', ['record' => $record])
                ),

                EditAction::make()
                    ->visible(fn ($record) =>
                        $record->status === 'draft' &&
                        auth()->user()->can('Edit Request For Quotations')
                    ),

                DeleteAction::make()
                    ->visible(fn ($record) =>
                        $record->status === 'draft' &&
                        auth()->user()->can('Delete Request For Quotations')
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
