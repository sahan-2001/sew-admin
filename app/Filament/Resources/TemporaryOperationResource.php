<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemporaryOperationResource\Pages;
use App\Models\TemporaryOperation;
use App\Models\ProductionLine;
use App\Models\Workstation;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms\Components\{Select, Textarea, Grid, Section, Repeater, TextInput};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TextInputFilter;
use Filament\Tables\Actions\Action;

class TemporaryOperationResource extends Resource
{
    protected static ?string $model = TemporaryOperation::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Temporary Operations';
    protected static ?string $navigationGroup = 'Daily Production';
    protected static ?int $navigationSort = 19;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('view temporary operations') ?? false;
    }
    
    public static function form(Form $form): Form
    {
        return $form
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
                                    ->searchable()
                                    ->options(function ($get) {
                                        $excludedStatuses = ['closed', 'invoiced', 'accepted', 'rejected'];

                                        if ($get('order_type') === 'customer_order') {
                                            return \App\Models\CustomerOrder::whereNotIn('status', $excludedStatuses)
                                                ->get()
                                                ->mapWithKeys(fn ($order) => [$order->order_id => "ID={$order->order_id} | Name={$order->name}"]);
                                        } elseif ($get('order_type') === 'sample_order') {
                                            return \App\Models\SampleOrder::whereNotIn('status', $excludedStatuses)
                                                ->get()
                                                ->mapWithKeys(fn ($order) => [$order->order_id => "ID={$order->order_id} | Name={$order->name}"]);
                                        }

                                        return [];
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $set('customer_id', null);
                                        $set('wanted_date', null);

                                        $orderType = $get('order_type');
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
                                    })
                                    ->disabled(fn ($get, $record) => $record !== null)
                                    ->dehydrated(),
                                ]),

                        TextInput::make('customer_id')
                            ->label('Customer ID')
                            ->disabled()
                            ->columns(1),

                        TextInput::make('wanted_date')
                            ->label('Wanted Date')
                            ->disabled()
                            ->columns(1),
                    ])
                    ->columns(2),

                    

                Section::make('Operation Details')
                ->schema([
                    Grid::make(1)
                        ->schema([
                            DatePicker::make('operation_date')
                                ->label('Operation Date')
                                ->required()
                                ->default(now())
                                ->reactive()
                                ->disabled(function () {
                                        return !auth()->user()->can('select_next_operation_dates');
                                    })
                                ->dehydrated(),
                        ]),
                ]),
                
                Section::make('Production Details')
                    ->schema([
                        // 🔹 First Grid
                        Forms\Components\Grid::make(1)->schema([
                            Textarea::make('description')
                                ->label('Operation Description')
                                ->nullable()
                                ->required()
                                ->columnSpan('full'),
                        ]),

                        Forms\Components\Grid::make(2)->schema([
                            Select::make('production_line_id')
                                ->label('Production Line')
                                ->options(\App\Models\ProductionLine::all()->pluck('name', 'id'))
                                ->reactive()
                                ->afterStateUpdated(fn (callable $set) => $set('workstation_id', null))
                                ->dehydrated(),

                            Select::make('workstation_id')
                                ->label('Workstation')
                                ->reactive()
                                ->options(function (callable $get) {
                                    $productionLineId = $get('production_line_id');

                                    return $productionLineId
                                        ? \App\Models\Workstation::where('production_line_id', $productionLineId)
                                            ->pluck('name', 'id')
                                        : \App\Models\Workstation::pluck('name', 'id'); 
                                })
                                ->required(fn (callable $get) => filled($get('production_line_id')))
                                ->dehydrated(),
                        ]),

                        // 🔹 Second Grid: Setup & Run Times
                        Forms\Components\Grid::make(4)->schema([
                            TextInput::make('machine_setup_time')
                                ->label('Machine Setup Time')
                                ->numeric()
                                ->default(0),

                            TextInput::make('machine_run_time')
                                ->label('Machine Run Time')
                                ->numeric()
                                ->default(0)
                                ->required(),

                            TextInput::make('labor_setup_time')
                                ->label('Labor Setup Time')
                                ->numeric()
                                ->default(0),

                            TextInput::make('labor_run_time')
                                ->label('Labor Run Time')
                                ->numeric()
                                ->default(0)
                                ->required(),
                        ]),

                        // 🔹 Third Grid: Selectables
                        Forms\Components\Grid::make(1)->schema([
                            Forms\Components\MultiSelect::make('employee_ids')
                                ->label('Employees')
                                ->options(\App\Models\User::role('employee')->pluck('name', 'id'))
                                ->searchable()
                                ->required(),

                            Forms\Components\MultiSelect::make('supervisor_ids')
                                ->label('Supervisors')
                                ->options(\App\Models\User::role('supervisor')->pluck('name', 'id'))
                                ->searchable(),

                            Forms\Components\MultiSelect::make('machine_ids')
                                ->label('Machines')
                                ->options(\App\Models\ProductionMachine::pluck('name', 'id'))
                                ->searchable(),

                            Forms\Components\MultiSelect::make('third_party_service_ids')
                                ->label('Third Party Services')
                                ->options(\App\Models\ThirdPartyService::pluck('name', 'id'))
                                ->searchable(),
                        ]),
                    ])
                    ->columns(1)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->searchable()->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('description')->label("Description")->limit(50)->toggleable(isToggledHiddenByDefault: true)->sortable(),
                TextColumn::make('order_type')->label('Order Type'),
                TextColumn::make('order_id')->label('Order ID')->sortable()->searchable()->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('status')->label('Status')->sortable(),
                TextColumn::make('operation_date')->label('Operation Date')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                ...(
                Auth::user()->can('view audit columns')
                    ? [
                        TextColumn::make('created_by')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                        TextColumn::make('updated_at')->label('Updated At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                    ]
                    : []
                    ),
            ])
            ->filters([
                SelectFilter::make('status')
                ->options([
                    'created' => 'Created',
                    'approved' => 'Approved',
                ])
                ->label('Filter by Status'),
            ])
            ->actions([
                ViewAction::make()
                    ->label('View')
                    ->modalHeading(fn ($record) => "Temporary Operation #{$record->id}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form(fn ($record) => [
                        Forms\Components\Section::make('Overview')
                            ->columns(2)
                            ->schema([
                                TextInput::make('id')->default(str_pad($record->id, 5, '0', STR_PAD_LEFT))->label('Operation ID')->disabled(),
                                TextInput::make('order_type')->default(ucwords(str_replace('_', ' ', $record->order_type)))->label('Order Type')->disabled(),
                                TextInput::make('order_id')->default($record->order_id)->label('Order ID')->disabled(),
                            ]),

                        Forms\Components\Section::make('Operation Details')
                            ->columns(2)
                            ->schema([
                                DatePicker::make('operation_date')->default($record->operation_date)->label('Operation Date')->disabled(),
                                Textarea::make('description')->default($record->description)->label('Operation Description')->disabled()->columnSpanFull(),

                                TextInput::make('production_line_id')
                                    ->default(optional($record->productionLine)->name ?? 'N/A')
                                    ->label('Production Line ID')->disabled(),

                                TextInput::make('workstation_id')
                                    ->default(optional($record->workstation)->name ?? 'N/A')
                                    ->label('Workstation ID')->disabled(),

                                TextInput::make('machine_setup_time')->default($record->machine_setup_time)->label('Machine Setup Time')->disabled(),
                                TextInput::make('machine_run_time')->default($record->machine_run_time)->label('Machine Run Time')->disabled(),
                                TextInput::make('labor_setup_time')->default($record->labor_setup_time)->label('Labor Setup Time')->disabled(),
                                TextInput::make('labor_run_time')->default($record->labor_run_time)->label('Labor Run Time')->disabled(),
                            ]),
                    ]),

                    Action::make('Download PDF')
                        ->label('Download PDF')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->url(fn ($record) => route('temporary-operation.print', $record))
                        ->openUrlInNewTab()
                        ->color('success'),

                    EditAction::make()
                        ->visible(fn ($record) => $record->status === 'created'),
                    DeleteAction::make()
                        ->visible(fn ($record) => $record->status === 'created'),
            ])
        ->defaultSort('id', 'desc') 
        ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTemporaryOperations::route('/'),
            'create' => Pages\CreateTemporaryOperation::route('/create'),
            'edit' => Pages\EditTemporaryOperation::route('/{record}/edit'),
        ];
    }
}
