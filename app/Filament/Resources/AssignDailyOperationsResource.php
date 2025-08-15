<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssignDailyOperationsResource\Pages;
use App\Filament\Resources\AssignDailyOperationsResource\Pages\EditAssignDailyOperations;
use App\Filament\Resources\AssignDailyOperationsResource\Pages\CreateAssignDailyOperations;
use App\Filament\Resources\AssignDailyOperationsResource\RelationManagers;
use App\Models\AssignDailyOperation;
use App\Models\ReleaseMaterial;
use App\Models\ReleaseMaterialLine;
use App\Models\ProductionLine;
use App\Models\Workstation;
use App\Models\Stock;
use App\Models\Operation;
use App\Models\CuttingRecord;
use App\Models\CuttingLabel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\{Select, Textarea, Grid, Section, Repeater, TextInput, Tabs};
use Filament\Forms\Components\ButtonAction;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions\Button;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Checkbox;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Carbon;


class AssignDailyOperationsResource extends Resource
{
    protected static ?string $model = AssignDailyOperation::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Daily Production';
    protected static ?string $navigationLabel = 'Assign Daily Operations';
    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Order & Operation Details')
                        ->schema([
                            Section::make('Operation Schedule')
                            ->schema([
                                DatePicker::make('operation_date')
                                    ->label('Operation Date')
                                    ->required()
                                    ->default(today())
                                    ->minDate(fn (string $context): ?Carbon => $context === 'create' ? today() : null)
                                    ->columnSpan(1)
                                    ->disabled(function (string $context) {
                                        if (auth()->user()?->can('select_next_operation_dates')) {
                                            return false;
                                        }
                                        return $context !== 'create'; 
                                        })
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('order_type', null);
                                        $set('order_id', null);
                                        $set('customer_id', null);
                                        $set('wanted_date', null);

                                        $set('workstation_id', null);
                                        $set('operation_id', null);
                                        $set('machine_setup_time', 0);
                                        $set('labor_setup_time', 0);
                                        $set('machine_run_time', 0);
                                        $set('labor_run_time', 0);
                                        $set('employee_ids', []);
                                        $set('supervisor_ids', []);
                                        $set('machine_ids', []);
                                        $set('third_party_service_ids', []);
                                        $set('target_durattion', null);
                                        $set('target_e', null);
                                        $set('target_m', null);
                                        $set('measurement_unit', null);
                                        $set('start_time', null);
                                        $set('end_time', null);
                                    }),
                            ])->columns(1),

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
                                                    // Clear all dependent fields when order_type changes
                                                    $set('order_id', null);
                                                    $set('customer_id', null);
                                                    $set('wanted_date', null);
                                                    $set('production_line_id', null);

                                                    $set('workstation_id', null);
                                                    $set('operation_id', null);
                                                    $set('machine_setup_time', 0);
                                                    $set('labor_setup_time', 0);
                                                    $set('machine_run_time', 0);
                                                    $set('labor_run_time', 0);
                                                    $set('employee_ids', []);
                                                    $set('supervisor_ids', []);
                                                    $set('machine_ids', []);
                                                    $set('third_party_service_ids', []);
                                                    $set('target_durattion', null);
                                                    $set('target_e', null);
                                                    $set('target_m', null);
                                                    $set('measurement_unit', null);
                                                    $set('start_time', null);
                                                    $set('end_time', null);

                                                }),

                                            Select::make('order_id')
                                                ->label('Order')
                                                ->required()
                                                ->disabled(fn ($get, $record) => $record !== null)
                                                ->dehydrated()
                                                ->searchable()
                                                ->helperText('You can select only orders with "cut" or "started" status.')
                                                ->options(function ($get) {
                                                    $orderType = $get('order_type');
                                                    $allowedStatuses = ['released', 'material released', 'cut', 'started'];

                                                    if ($orderType === 'customer_order') {
                                                        return \App\Models\CustomerOrder::with('customer')
                                                            ->whereIn('status', $allowedStatuses)
                                                            ->get()
                                                            ->mapWithKeys(function ($order) {
                                                                $customerName = $order->customer->name ?? 'Unknown Customer';
                                                                $label = "Order ID - {$order->order_id} | Name - {$order->name} | Customer - {$customerName}";
                                                                return [$order->order_id => $label];
                                                            });
                                                    } elseif ($orderType === 'sample_order') {
                                                        return \App\Models\SampleOrder::with('customer')
                                                            ->whereIn('status', $allowedStatuses)
                                                            ->get()
                                                            ->mapWithKeys(function ($order) {
                                                                $customerName = $order->customer->name ?? 'Unknown Customer';
                                                                $label = "Order ID - {$order->order_id} | Name - {$order->name} | Customer - {$customerName}";
                                                                return [$order->order_id => $label];
                                                            });
                                                    }

                                                    return [];
                                                })
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    $set('customer_id', null);
                                                    $set('wanted_date', null);

                                                    $orderType = $get('order_type');
                                                    if ($orderType && $state) {
                                                        $order = null;

                                                        if ($orderType === 'customer_order') {
                                                            $order = \App\Models\CustomerOrder::find($state);
                                                        } elseif ($orderType === 'sample_order') {
                                                            $order = \App\Models\SampleOrder::find($state);
                                                        }

                                                        if (!$order || !in_array($order->status, ['cut', 'started'])) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Invalid Order Status')
                                                                ->body('Only orders with "Cut" or "Started" status can be selected.')
                                                                ->danger()
                                                                ->duration(8000)
                                                                ->send();

                                                            // Clear form-related fields
                                                            $set('order_id', null);
                                                            $set('customer_id', null);
                                                            $set('wanted_date', null);
                                                            $set('workstation_id', null);
                                                            $set('operation_id', null);
                                                            $set('machine_setup_time', 0);
                                                            $set('labor_setup_time', 0);
                                                            $set('machine_run_time', 0);
                                                            $set('labor_run_time', 0);
                                                            $set('employee_ids', []);
                                                            $set('supervisor_ids', []);
                                                            $set('machine_ids', []);
                                                            $set('third_party_service_ids', []);
                                                            $set('target_durattion', null);
                                                            $set('target_e', null);
                                                            $set('target_m', null);
                                                            $set('measurement_unit', null);
                                                            $set('start_time', null);
                                                            $set('end_time', null);
                                                            return;
                                                        }

                                                        $set('customer_id', $order->customer_id ?? 'N/A');
                                                        $set('wanted_date', $order->wanted_delivery_date ?? 'N/A');

                                                        $cuttingRecordExists = \App\Models\CuttingRecord::where('order_type', $orderType)
                                                            ->where('order_id', $state)
                                                            ->exists();

                                                        if (!$cuttingRecordExists) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('No Cutting Record Found')
                                                                ->body('The selected order does not have any Cutting Records.')
                                                                ->danger()
                                                                ->persistent()
                                                                ->send();

                                                            $set('order_id', null);
                                                            $set('customer_id', null);
                                                            $set('wanted_date', null);
                                                            $set('workstation_id', null);
                                                            $set('operation_id', null);
                                                            $set('machine_setup_time', 0);
                                                            $set('labor_setup_time', 0);
                                                            $set('machine_run_time', 0);
                                                            $set('labor_run_time', 0);
                                                            $set('employee_ids', []);
                                                            $set('supervisor_ids', []);
                                                            $set('machine_ids', []);
                                                            $set('third_party_service_ids', []);
                                                            $set('target_durattion', null);
                                                            $set('target_e', null);
                                                            $set('target_m', null);
                                                            $set('measurement_unit', null);
                                                            $set('start_time', null);
                                                            $set('end_time', null);
                                                        }
                                                    }
                                                })

                                                ->afterStateHydrated(function ($state, $set, $get, $record) {
                                                    if ($record && $state) {
                                                        $orderType = $get('order_type');

                                                        if ($orderType === 'customer_order') {
                                                            $order = \App\Models\CustomerOrder::find($state);
                                                        } elseif ($orderType === 'sample_order') {
                                                            $order = \App\Models\SampleOrder::find($state);
                                                        } else {
                                                            $order = null;
                                                        }

                                                        if ($order) {
                                                            $set('customer_id', $order->customer_id ?? 'N/A');
                                                            $set('wanted_date', $order->wanted_delivery_date ?? 'N/A');
                                                        } else {
                                                            $set('customer_id', 'N/A');
                                                            $set('wanted_date', 'N/A');
                                                        }
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
                    
                    Tabs\Tab::make('Operation Assignment')
                        ->schema([
                            Section::make('Daily Operation Lines')
                                ->schema([
                                    Grid::make()->columns(1)
                                        ->schema([
                                            Repeater::make('daily_operations')
                                                ->schema([
                                                    Grid::make(4)->schema([
                                                        Select::make('production_line_id')
                                                            ->label('Production Line')
                                                            ->options(fn () => \App\Models\ProductionLine::all()
                                                                ->mapWithKeys(fn ($line) => [
                                                                    $line->id => "{$line->id} | Name - {$line->name}"
                                                                ])
                                                            )
                                                            ->reactive()
                                                            ->afterStateUpdated(fn ($set) => $set('workstation_id', null))
                                                            ->required()
                                                            ->disabled(fn ($get) => $get('disabled')),

                                                        Select::make('workstation_id')
                                                            ->label('Workstation')
                                                            ->options(fn (callable $get) => $get('production_line_id')
                                                                ? \App\Models\Workstation::where('production_line_id', $get('production_line_id'))
                                                                    ->get()
                                                                    ->mapWithKeys(fn ($workstation) => [
                                                                        $workstation->id => "{$workstation->id} | Name - {$workstation->name}"
                                                                    ])
                                                                : [])
                                                            ->reactive()
                                                            ->afterStateUpdated(fn ($set) => $set('operation_id', null))
                                                            ->required()
                                                            ->disabled(fn (callable $get) => $get('production_line_id') === null || $get('disabled')),


                                                        Select::make('operation_id')
                                                            ->label('Operation')
                                                            ->options(fn (callable $get) =>
                                                                $get('workstation_id')
                                                                    ? \App\Models\Operation::where('workstation_id', $get('workstation_id'))
                                                                        ->get()
                                                                        ->mapWithKeys(fn ($operation) => [
                                                                            $operation->id => "{$operation->id} | Description - {$operation->description}"
                                                                        ])
                                                                    : []
                                                            )
                                                            ->required()
                                                            ->columns(2)
                                                            ->disabled(fn (callable $get) => $get('workstation_id') === null || $get('disabled'))
                                                            ->reactive()
                                                            ->afterStateUpdated(function ($state, callable $set) {
                                                                $operation = \App\Models\Operation::with(['employee', 'supervisor', 'machine', 'thirdPartyService'])->find($state);

                                                                if ($operation) {
                                                                    $set('employee_ids', $operation->employee_id ? [$operation->employee_id] : []);
                                                                    $set('supervisor_ids', $operation->supervisor_id ? [$operation->supervisor_id] : []);

                                                                    $set('machine_ids', $operation->machine_id ? [$operation->machine_id] : []);

                                                                    $set('third_party_service_ids', $operation->third_party_service_id ? [$operation->third_party_service_id] : []);

                                                                    $set('machine_setup_time', $operation->machine_setup_time ?? 0);
                                                                    $set('machine_run_time', $operation->machine_run_time ?? 0);
                                                                    $set('labor_setup_time', $operation->labor_setup_time ?? 0);
                                                                    $set('labor_run_time', $operation->labor_run_time ?? 0);
                                                                } else {
                                                                    $set('employee_ids', []);
                                                                    $set('supervisor_ids', []);
                                                                    $set('machine_ids', []);
                                                                    $set('third_party_service_ids', []);
                                                                    $set('machine_setup_time', 0);
                                                                    $set('machine_run_time', 0);
                                                                    $set('labor_setup_time', 0);
                                                                    $set('labor_run_time', 0);
                                                                }
                                                            }),

                                                        TextInput::make('machine_setup_time')->label('Machine Setup Time')->numeric()->default(0)->reactive()->disabled(fn ($get) => $get('disabled')),
                                                        TextInput::make('labor_setup_time')->label('Labor Setup Time')->numeric()->default(0)->reactive()->disabled(fn ($get) => $get('disabled')),
                                                        TextInput::make('machine_run_time')->label('Machine Run Time')->numeric()->default(0)->reactive()->disabled(fn ($get) => $get('disabled')),
                                                        TextInput::make('labor_run_time')->label('Labor Run Time')->numeric()->default(0)->reactive()->disabled(fn ($get) => $get('disabled')),

                                                        Forms\Components\MultiSelect::make('employee_ids')
                                                            ->label('Employees')
                                                            ->options(
                                                                \App\Models\User::role('employee')
                                                                    ->get()
                                                                    ->mapWithKeys(fn ($user) => [
                                                                        $user->id => "ID - {$user->id} | Name - {$user->name}"
                                                                    ])
                                                            )                                                            
                                                            ->searchable()
                                                            ->columnSpanFull()
                                                            ->disabled(fn ($get) => $get('disabled')),

                                                        Forms\Components\MultiSelect::make('supervisor_ids')
                                                            ->label('Supervisors')
                                                            ->options(
                                                                \App\Models\User::role('supervisor')
                                                                    ->get()
                                                                    ->mapWithKeys(fn ($user) => [
                                                                        $user->id => "ID - {$user->id} | Name - {$user->name}"
                                                                    ])
                                                            )
                                                            ->searchable()
                                                            ->columnSpanFull()
                                                            ->disabled(fn ($get) => $get('disabled')),

                                                        Forms\Components\MultiSelect::make('machine_ids')
                                                            ->label('Automated Machines')
                                                            ->options(
                                                                \App\Models\ProductionMachine::get()
                                                                    ->mapWithKeys(fn ($machine) => [
                                                                        $machine->id => "ID - {$machine->id} | Name - {$machine->name}"
                                                                    ])
                                                            )
                                                            ->searchable()
                                                            ->columnSpanFull()
                                                            ->disabled(fn ($get) => $get('disabled')),

                                                        Forms\Components\MultiSelect::make('third_party_service_ids')
                                                            ->label('Third Party Services')
                                                            ->options(
                                                                \App\Models\ThirdPartyService::get()
                                                                    ->mapWithKeys(fn ($service) => [
                                                                        $service->id => "ID - {$service->id} | Name - {$service->name}"
                                                                    ])
                                                            )
                                                            ->searchable()
                                                            ->columnSpanFull()
                                                            ->disabled(fn ($get) => $get('disabled')),

                                                        Select::make('target_duration')->label('Target Duration')->options(['hourly' => 'Hourly', 'daily' => 'Daily'])->disabled(fn ($get) => $get('disabled')),
                                                        TextInput::make('target_e')->label('Target per Employee')->numeric()->disabled(fn ($get) => $get('disabled')),
                                                        TextInput::make('target_m')->label('Target per Machine')->numeric()->disabled(fn ($get) => $get('disabled')),
                                                        Select::make('measurement_unit')
                                                            ->label('Measurement Unit')
                                                            ->options(['pcs' => 'Pieces', 'kgs' => 'Kilograms', 'liters' => 'Liters', 'minutes' => 'Minutes', 'hours' => 'Hours'])->disabled(fn ($get) => $get('disabled'))
                                                            ->default('pcs'),
                                                    ]),
                                                ])
                                                ->itemLabel(fn (array $state): ?string => ($state['workstation_name'] ?? '') . ' - ' . ($state['operation_description'] ?? ''))
                                                ->defaultItems(1)
                                                ->minItems(1)
                                                ->columns(1)
                                                ->columnSpanFull()
                                        ]),
                                    ]),
                        ]),

                    Tabs\Tab::make('Cut Piece Labels')
                    ->schema([
                        Section::make('Existing Labels')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('range_start_label_id')
                                            ->label('Start Label')
                                            ->options(function (callable $get) {
                                                $orderType = $get('order_type');
                                                $orderId = $get('order_id');

                                                return \App\Models\CuttingLabel::where('order_type', $orderType)
                                                    ->where('order_id', $orderId)
                                                    ->orderBy('label')
                                                    ->pluck('label', 'id');
                                            })
                                            ->reactive()
                                            ->searchable()
                                            ->hidden(fn (callable $get) => !($get('order_type') && $get('order_id'))),

                                        Select::make('range_end_label_id')
                                            ->label('End Label')
                                            ->options(function (callable $get) {
                                                $orderType = $get('order_type');
                                                $orderId = $get('order_id');

                                                return \App\Models\CuttingLabel::where('order_type', $orderType)
                                                    ->where('order_id', $orderId)
                                                    ->orderBy('label')
                                                    ->pluck('label', 'id');
                                            })
                                            ->reactive()
                                            ->searchable()
                                            ->hidden(fn (callable $get) => !($get('order_type') && $get('order_id'))),

                                            \Filament\Forms\Components\Actions::make([
                                            \Filament\Forms\Components\Actions\Action::make('apply_range')
                                                ->label('Apply Label Range')
                                                ->action(function ($get, $set) {
                                                    $orderType = $get('order_type');
                                                    $orderId = $get('order_id');
                                                    $startId = $get('range_start_label_id');
                                                    $endId = $get('range_end_label_id');

                                                    if (!$orderType || !$orderId || !$startId || !$endId) {
                                                        return;
                                                    }

                                                    $labels = \App\Models\CuttingLabel::where('order_type', $orderType)
                                                        ->where('order_id', $orderId)
                                                        ->orderBy('label')
                                                        ->get();

                                                    $inRange = false;
                                                    $rangeIds = [];

                                                    foreach ($labels as $label) {
                                                        if ($label->id == $startId || $label->id == $endId) {
                                                            $rangeIds[] = $label->id;
                                                            if ($startId !== $endId) {
                                                                $inRange = !$inRange;
                                                            } else {
                                                                break;
                                                            }
                                                        } elseif ($inRange) {
                                                            $rangeIds[] = $label->id;
                                                        }
                                                    }

                                                    $existing = $get('selected_label_ids') ?? [];
                                                    $set('selected_label_ids', array_unique([...$existing, ...$rangeIds]));
                                                })
                                                ->color('primary'),
                                        ])
                                        ->hidden(fn (callable $get) => !($get('order_type') && $get('order_id'))),
                                    ]),
                                    
                                Grid::make(2)
                                    ->schema([
                                        Placeholder::make('selected_label_count')
                                            ->label('Selected Labels Count')
                                            ->content(function (callable $get) {
                                                $selected = $get('selected_label_ids') ?? [];
                                                return count($selected) . ' label(s) selected';
                                            })
                                            ->hidden(fn (callable $get) => !($get('order_type') && $get('order_id'))),
                                            
                                        Checkbox::make('select_all_labels')
                                            ->label('Select All Labels')
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                                $orderType = $get('order_type');
                                                $orderId = $get('order_id');

                                                if (!$orderType || !$orderId) {
                                                    return;
                                                }

                                                $labelIds = \App\Models\CuttingLabel::where('order_type', $orderType)
                                                    ->where('order_id', $orderId)
                                                    ->pluck('id')
                                                    ->toArray();

                                                $set('selected_label_ids', $state ? $labelIds : []);
                                            })
                                            ->hidden(fn (callable $get) => !($get('order_type') && $get('order_id'))),
                                        ]),

                                Grid::make(1)
                                    ->schema([
                                        CheckboxList::make('selected_label_ids')
                                            ->label('Cutting Labels')
                                            ->required()
                                            ->options(function (callable $get) {
                                                $orderType = $get('order_type');
                                                $orderId = $get('order_id');

                                                if (!$orderType || !$orderId) {
                                                    return [];
                                                }

                                                return \App\Models\CuttingLabel::where('order_type', $orderType)
                                                    ->where('order_id', $orderId)
                                                    ->pluck('label', 'id');
                                            })
                                            ->columns(3)
                                            ->reactive()
                                            ->searchable()
                                            ->hidden(fn (callable $get) => !($get('order_type') && $get('order_id')))
                                            ->afterStateHydrated(function ($component, $record) {
                                                if ($record) {
                                                    $component->state($record->labels->pluck('id')->toArray());
                                                }
                                            })
                                            ->dehydrated(false)
                                            ->saveRelationshipsUsing(function ($record, $state) {
                                                // Use sync without detaching to maintain existing relationships
                                                $record->labels()->syncWithPivotValues($state ?? [], [], false);
                                            }),
                                    ])
                            ])
                            ->collapsible()
                    ])
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_type'),
                Tables\Columns\TextColumn::make('order_id')->sortable()->searchable()
                    ->formatStateUsing(fn (?string $state): string => str_pad($state, 5, '0', STR_PAD_LEFT)),
                Tables\Columns\TextColumn::make('operation_date')->sortable(),
                Tables\Columns\TextColumn::make('status'),
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
                Tables\Filters\SelectFilter::make('order_type')
                    ->label('Order Type')
                    ->options([
                        'customer_order' => 'Customer Order',
                        'sample_order' => 'Sample Order',
                    ]),

                Tables\Filters\Filter::make('operation_date')
                    ->label('Operation Date')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->label('Select Operation Date')
                            ->closeOnDateSelection()
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when($data['date'], fn ($q, $date) =>
                            $q->whereDate('operation_date', $date)
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('Print Report')
                    ->icon('heroicon-o-printer')
                    ->url(fn (AssignDailyOperation $record): string => route('assign-daily-operations.print', $record))
                    ->openUrlInNewTab(),
                
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status !== 'recorded'),
            ])
        ->defaultSort('id', 'desc') 
        ->recordUrl(null);
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
            'index' => Pages\ListAssignDailyOperations::route('/'),
            'create' => Pages\CreateAssignDailyOperations::route('/create'),
            'edit' => Pages\EditAssignDailyOperations::route('/{record}/edit'),
        ];
    }
}