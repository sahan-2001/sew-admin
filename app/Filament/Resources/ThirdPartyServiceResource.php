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
use App\Models\AssignDailyOperation;
use App\Models\Category;
use App\Models\CustomerAdvanceInvoice;
use App\Models\CuttingRecord;
use App\Models\CuttingStation;
use App\Models\EndOfDayReport;
use App\Models\EnterPerformanceRecord;
use App\Models\NonInventoryCategory;
use App\Models\NonInventoryItem;
use App\Models\ProductionLine;
use App\Models\Workstation;
use App\Models\Operation;
use App\Models\ProductionMachine;
use App\Models\RegisterArrival;
use App\Models\ReleaseMaterial;
use App\Models\SupplierAdvanceInvoice;
use App\Models\PurchaseOrderInvoice;
use App\Models\TemporaryOperation;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\DateFilter; 
use Filament\Tables\Filters\Layout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TextFilter;
use Illuminate\Support\Carbon;

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
            Section::make('Supplier Details')
                ->columns(5)
                ->schema([
                    Select::make('supplier_id')
                        ->label('Supplier')
                        ->relationship('supplier', 'name')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $supplier = \App\Models\Supplier::find($state);
                            $set('supplier_name', $supplier?->name);
                            $set('supplier_email', $supplier?->email);
                            $set('supplier_phone', $supplier?->phone_1);
                        }),

                    TextInput::make('supplier_name')
                        ->label('Name')
                        ->readonly()
                        ->disabled()
                        ->afterStateHydrated(function (callable $set, $state, $record) {
                            if ($record?->supplier) {
                                $set('supplier_name', $record->supplier->name);
                            }
                        }),

                    TextInput::make('supplier_email')
                        ->label('Email')
                        ->readonly()
                        ->disabled()
                        ->afterStateHydrated(function (callable $set, $state, $record) {
                            if ($record?->supplier) {
                                $set('supplier_email', $record->supplier->email);
                            }
                        }),

                    TextInput::make('supplier_phone')
                        ->label('Phone')
                        ->readonly()
                        ->disabled()
                        ->afterStateHydrated(function (callable $set, $state, $record) {
                            if ($record?->supplier) {
                                $set('supplier_phone', $record->supplier->phone_1);
                            }
                        }),

                    TextInput::make('name')
                        ->label('Service Name')
                        ->required()
                        ->maxLength(255),
                    ]),


            Section::make('Third Party Service Processes')
                    ->schema([
                        Repeater::make('processes')
                            ->relationship('processes')
                            ->columnSpan(5)
                            ->schema([
                                \Filament\Forms\Components\Grid::make(9) 
                                    ->schema([
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
                                                'assign_daily_operations' => 'Assign Daily Operations',
                                                'categories' => 'Inv Item Categories',
                                                'customer_advance_invoices' => 'Customer Advance Invoices',
                                                'cutting_records' => 'Cutting Records',
                                                'cutting_stations' => 'Cutting Stations',
                                                'end_of_day_reports' =>'End Of Day Reports',
                                                'enter_performance_records' => 'Enter Performance Records',
                                                'non_inventory_categories' => 'Non Inv Item Categories',
                                                'non_inventory_items' => 'Non Inventory Items',
                                                'production_lines' => 'Production Lines',
                                                'workstations' => 'Workstations',
                                                'operations' => 'Pre-Defined Operations',
                                                'Production_machines' => 'Production Machines',
                                                'register_arrivals' => 'Register Arrivals',
                                                'release_materials' => 'Release Materials',
                                                'supplier_advance_invoices' => 'Supplier Advance Invoices',
                                                'purchase_order_invoices' => 'Purchase Order Invoices',
                                                'temporary_operations' => 'Temporary Operations',
                                                'users' => 'System Users',
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
                                        'customer_orders' => CustomerOrder::all()->pluck('name', 'order_id')->filter(), 
                                        'sample_orders' => SampleOrder::all()->pluck('name', 'order_id')->filter(), 
                                        'inventory_locations' => InventoryLocation::all()->pluck('name', 'id')->filter(),
                                        'inventory_items' => InventoryItem::all()->pluck('name', 'id')->filter(),
                                        'warehouses' => Warehouse::all()->pluck('name', 'id')->filter(),
                                        'assign_daily_operations' => AssignDailyOperation::all()->pluck('order_type', 'order_id', 'id')->filter(),
                                        'categories' => Category::all()->pluck('name', 'id')->filter(),
                                        'customer_advance_invoices' => CustomerAdvanceInvoice::all()->pluck('name', 'id')->filter(),
                                        'cutting_records' => CuttingRecord::all()->pluck('name', 'id')->filter(),
                                        'cutting_stations' => CuttingStation::all()->pluck('name', 'id')->filter(),
                                        'end_of_day_reports' => EndOfDayReport::all()->pluck('name', 'id')->filter(),
                                        'enter_performance_records' => EnterPerformanceRecord::all()->pluck('name', 'id')->filter(),
                                        'non_inventory_categories' => NonInventoryCategory::all()->pluck('name', 'id')->filter(),
                                        'non_inventory_items' => NonInventoryItem::all()->pluck('name', 'id')->filter(),
                                        'production_lines' => ProductionLine::all()->pluck('name', 'id')->filter(),
                                        'workstations' => Workstation::all()->pluck('name', 'id')->filter(),
                                        'operations' => Operation::all()->pluck('name', 'id')->filter(),
                                        'Production_machines' => ProductionMachine::all()->pluck('name', 'id')->filter(),
                                        'register_arrivals' => RegisterArrival::all()->pluck('name', 'id')->filter(),
                                        'release_materials' => ReleaseMaterial::all()->pluck('name', 'id')->filter(),
                                        'supplier_advance_invoices' => SupplierAdvanceInvoice::all()->pluck('name', 'id')->filter(),
                                        'purchase_order_invoices' => PurchaseOrderInvoice::all()->pluck('name', 'id')->filter(),
                                        'temporary_operations' => TemporaryOperation::all()->pluck('name', 'id')->filter(),
                                        'users' => User::all()->pluck('name', 'id')->filter(),
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
                ]),

            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Service ID')
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('name')->label('Service Name'),
                TextColumn::make('supplier.name')->label('Supplier'),
                TextColumn::make('service_total')->label('Service Total'),
                TextColumn::make('remaining_balance')->label('Remaining Balance'),
                TextColumn::make('status')->label('Status'),
                ...(
                    Auth::user()->can('view audit columns')
                        ? [
                            TextColumn::make('created_by')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                            TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                            TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                            TextColumn::make('updated_at')->label('Updated At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                        ]
                        : []
                ),
            ])
            ->filters([
                Filter::make('id')
                    ->form([
                        TextInput::make('id')
                            ->label('Service ID')
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['id'],
                            fn ($query, $value) => $query->where('id', $value)
                        );
                    }),

                Filter::make('supplier_id')
                    ->label('Supplier ID')
                    ->form([
                        TextInput::make('supplier_id')
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['supplier_id'],
                            fn ($query, $value) => $query->where('supplier_id', $value)
                        );
                    }),

                Filter::make('service_total')
                    ->label('Service Total')
                    ->form([
                        TextInput::make('service_total')
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['service_total'],
                            fn ($query, $value) => $query->where('service_total', $value)
                        );
                    }),

                Filter::make('created_at')
                    ->label('Entered Date')
                    ->form([
                        DatePicker::make('created_date')
                            ->maxDate(Carbon::today())
                            ->label('Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['created_date'],
                            fn ($query, $date) => $query->whereDate('created_at', $date)
                        );
                    }),
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
