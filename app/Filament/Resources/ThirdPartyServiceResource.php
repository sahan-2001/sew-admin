<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ThirdPartyServiceResource\Pages;
use App\Models\ThirdPartyService;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use App\Models\InventoryLocation;
use App\Models\InventoryItem;
use App\Models\Warehouse;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\Facades\Auth;

class ThirdPartyServiceResource extends Resource
{
    protected static ?string $model = ThirdPartyService::class;

    protected static ?string $navigationGroup = 'Services';
    protected static ?string $navigationLabel = '3rd Party Services';
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Select::make('supplier_id')
                ->label('Supplier')
                ->relationship('supplier', 'name')
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $supplier = Supplier::find($state);
                    $set('supplier_name', $supplier ? $supplier->name : null);
                    $set('supplier_email', $supplier ? $supplier->email : null);
                    $set('supplier_phone', $supplier ? $supplier->phone_1 : null);
                }),

            TextInput::make('supplier_name')->label('Name')->readonly()->disabled(),
            TextInput::make('supplier_email')->label('Email')->readonly()->disabled(),
            TextInput::make('supplier_phone')->label('Phone')->readonly()->disabled(),
            TextInput::make('name')
                ->label('Service Name')
                ->required()
                ->maxLength(255),

            Repeater::make('processes')
    ->relationship('processes')
    ->columnSpan(5)
    ->schema([
        \Filament\Forms\Components\Grid::make(9) // 9-column layout for better alignment
            ->schema([
                TextInput::make('sequence_number')
                    ->numeric()
                    ->required()
                    ->label('Seq.')
                    ->columnSpan(1),
                
                TextInput::make('description')
                    ->required()
                    ->label('Description')
                    ->columnSpan(3),
                
                Select::make('related_table')
                    ->label('Related Table')
                    ->options([
                        'customers' => 'Customers',
                        'suppliers' => 'Suppliers',
                        'purchase_orders' => 'Purchase Orders',
                        'customer_orders' => 'Customer Orders',
                        'sample_orders' => 'Sample Orders',
                        'inventory_locations' => 'Inventory Locations',
                        'inventory_items' => 'Inventory Items',
                        'warehouses' => 'Warehouses',
                    ])
                    ->reactive()
                    ->required()
                    ->columnSpan(2),
                
                Select::make('related_record_id')
    ->label('Related Record')
    ->options(function ($get) {
        $relatedTable = $get('related_table');
        if ($relatedTable) {
            return match ($relatedTable) {
                'customers' => Customer::all()->pluck('name', 'customer_id')->filter(),
                'suppliers' => Supplier::all()->pluck('name', 'supplier_id')->filter(),
                'purchase_orders' => PurchaseOrder::all()->pluck('provider_name', 'id')->filter(),
                'customer_orders' => CustomerOrder::all()->pluck('name', 'order_id')->filter(), // Use 'name' instead of 'customer_name'
                'sample_orders' => SampleOrder::all()->pluck('name', 'order_id')->filter(), // Use 'name' instead of 'customer_name'
                'inventory_locations' => InventoryLocation::all()->pluck('name', 'id')->filter(),
                'inventory_items' => InventoryItem::all()->pluck('name', 'id')->filter(),
                'warehouses' => Warehouse::all()->pluck('name', 'id')->filter(),
                default => [],
            };
        }
        return [];
    })
    ->searchable()
    ->required()
    ->columnSpan(3)
    ->dehydrated()
    ->saveRelationshipsUsing(function ($state, $record, $set) {
        $record->related_record_id = $state;
        $record->save();
    }),

            

                // Hidden field to store the related record ID
                TextInput::make('related_record_id')
                    ->label('Related Record ID')
                    ->disabled()
                    ->hidden() // This will be saved but not displayed in the form
                    ->dehydrated(true),
                
                // SECOND ROW: UoM, Amount, Unit Rate, and Total
                Select::make('unit_of_measurement')
                    ->label('UoM')
                    ->options([
                        'hours' => 'Hours',
                        'minutes' => 'Minutes',
                        'pcs' => 'Pieces',
                        'liters' => 'Liters',
                    ])
                    ->searchable()
                    ->required()
                    ->columnSpan(2),
                
                TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->label('Amount')
                    ->columnSpan(2),

                TextInput::make('unit_rate')
                    ->numeric()
                    ->required()
                    ->label('Unit Rate')
                    ->columnSpan(2),

                TextInput::make('total')
                    ->numeric()
                    ->disabled()
                    ->default(0)
                    ->dehydrateStateUsing(fn ($get) => $get('amount') * $get('unit_rate'))
                    ->label('Total')
                    ->columnSpan(2),
            ]),
    ])
    ->createItemButtonLabel('Add Process')

        ]);
}


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Service ID')->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('name')->label('Service Name'),
                TextColumn::make('supplier.name')->label('Supplier'),
                TextColumn::make('created_at')->label('Created Date')->date(),
                ...(
                Auth::user()->can('view audit columns')
                    ? [
                        TextColumn::make('created_by')->label('Created By')->toggleable()->sortable(),
                        TextColumn::make('updated_by')->label('Updated By')->toggleable()->sortable(),
                        TextColumn::make('updated_at')->label('Updated At')->toggleable()->dateTime()->sortable(),
                    ]
                    : []
                    ),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn ($record) => 
                        auth()->user()->can('edit third party services') 
                    ),

                DeleteAction::make()
                    ->visible(fn ($record) => 
                        auth()->user()->can('delete third party services') 
                    ),
            ])
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListThirdPartyServices::route('/'),
            'create' => Pages\CreateThirdPartyService::route('/create'),
            'edit' => Pages\EditThirdPartyService::route('/{record}/edit'),
        ];
    }
}
