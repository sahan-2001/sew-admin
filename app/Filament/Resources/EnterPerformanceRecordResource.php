<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnterPerformanceRecordResource\Pages;
use App\Filament\Resources\EnterPerformanceRecordResource\RelationManagers;
use App\Models\EnterPerformanceRecord;
use App\Models\ProductionLine;
use App\Models\Workstation;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use App\Models\AssignDailyOperation;
use App\Models\AssignDailyOperationLine;
use App\Models\UMOperation;
use App\Models\UMOperationLine;
use App\Models\ProductionMachine;
use App\Models\ThirdPartyService;
use App\Models\User;    
use App\Models\TemporaryOperation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\{Select, Textarea, Grid, Section, Repeater, TextInput, Hidden, DatePicker, TimePicker};
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;

class EnterPerformanceRecordResource extends Resource
{
    protected static ?string $model = EnterPerformanceRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Enter Daily Operation Performance';
    protected static ?string $navigationGroup = 'Daily Production';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Performance Tabs')
                    ->contained()
                    ->columnSpan(['lg' => 2])
                    ->tabs([
                        Tab::make('Order & Operations Selection')
                            ->schema([
                                Section::make('Order Details')
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
                                            ->afterStateUpdated(function ($state, $set) {
                                                $set('order_id', null);
                                                $set('customer_id', null);
                                                $set('wanted_date', null);
                                                $set('selected_operation', null);
                                                $set('operation_type', null);
                                                $set('performance_records', []);
                                            }),

                                        Select::make('order_id')
                                            ->label('Order')
                                            ->required()
                                            ->options(function ($get) {
                                                return $get('order_type') === 'customer_order' ?
                                                    CustomerOrder::pluck('name', 'order_id') :
                                                    SampleOrder::pluck('name', 'order_id');
                                            })
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                $set('customer_id', null);
                                                $set('wanted_date', null);
                                                $set('selected_operation', null);
                                                $set('operation_type', null);
                                                $set('performance_records', []);
                                                
                                                $orderType = $get('order_type');
                                                $order = $orderType === 'customer_order' ?
                                                    CustomerOrder::find($state) :
                                                    SampleOrder::find($state);

                                                if ($order) {
                                                    $set('customer_id', $order->customer_id ?? 'N/A');
                                                    $set('wanted_date', $order->wanted_delivery_date ?? 'N/A');
                                                }
                                            }),
                                    ]),
                                
                                TextInput::make('customer_id')->label('Customer ID')->disabled(),
                                TextInput::make('wanted_date')->label('Wanted Date')->disabled(),
                                
                                DatePicker::make('operation_date')
                                    ->label('Operation Date')
                                    ->default(today())
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('Y-m-d')
                                    ->maxDate(today()) 
                                    ->disabled(function () {
                                        return !auth()->user()->can('select_previous_performance_dates');
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($set) {
                                        $set('order_type', null);
                                        $set('order_id', null);
                                        $set('customer_id', null);
                                        $set('wanted_date', null);
                                        $set('selected_operation', null);
                                        $set('operation_type', null);
                                        $set('performance_records', []);
                                    }),

                                Section::make('Operation Selection')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('operation_type')
                                                    ->label('Operation Type')
                                                    ->options([
                                                        'assigned' => 'Assigned Operation',
                                                        'um' => 'UM Operation',
                                                        'temp' => 'Temporary Operation',
                                                    ])
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, $set) {
                                                        $set('selected_operation', null);
                                                    }),
                                                    
                                                Select::make('selected_operation')
                                                    ->label('Select Operation')
                                                    ->options(function ($get) {
                                                        if (!$get('order_type') || !$get('order_id') || !$get('operation_type')) {
                                                            return [];
                                                        }
                                                        
                                                        $orderType = $get('order_type');
                                                        $orderId = $get('order_id');
                                                        $operationType = $get('operation_type');
                                                        $operationDate = $get('operation_date');
                                                        
                                                        switch ($operationType) {
                                                            case 'assigned':
                                                                return AssignDailyOperationLine::with([
                                                                        'productionLine', 
                                                                        'workstation', 
                                                                        'operation',
                                                                        'assignDailyOperation'
                                                                    ])
                                                                    ->whereHas('assignDailyOperation', function($query) use ($orderType, $orderId, $operationDate) {
                                                                        $query->where('order_type', $orderType)
                                                                            ->where('order_id', $orderId)
                                                                            ->whereDate('operation_date', $operationDate);
                                                                    })
                                                                    ->get()
                                                                    ->mapWithKeys(function ($line) {
                                                                        return [
                                                                            $line->id => sprintf(
                                                                                "Line: %s, WS: %s, Op: %s",
                                                                                $line->productionLine->name ?? 'N/A',
                                                                                $line->workstation->name ?? 'N/A',
                                                                                $line->operation->name ?? 'N/A'
                                                                            )
                                                                        ];
                                                                    });
                                                            
                                                            case 'um':
                                                                return UMOperationLine::with([
                                                                        'productionLine', 
                                                                        'workstation', 
                                                                        'operation',
                                                                        'umOperation'
                                                                    ])
                                                                    ->whereHas('umOperation', function($query) use ($orderType, $orderId, $operationDate) {
                                                                        $query->where('order_type', $orderType)
                                                                            ->where('order_id', $orderId)
                                                                            ->whereDate('operation_date', $operationDate);
                                                                    })
                                                                    ->get()
                                                                    ->mapWithKeys(function ($line) {
                                                                        return [
                                                                            $line->id => sprintf(
                                                                                "Line: %s, WS: %s, Op: %s",
                                                                                $line->productionLine->name ?? 'N/A',
                                                                                $line->workstation->name ?? 'N/A',
                                                                                $line->operation->name ?? 'N/A'
                                                                            )
                                                                        ];
                                                                    });
                                                            
                                                            case 'temp':
                                                                return TemporaryOperation::with(['productionLine', 'workstation'])
                                                                    ->where('order_type', $orderType)
                                                                    ->where('order_id', $orderId)
                                                                    ->get()
                                                                    ->mapWithKeys(function ($operation) {
                                                                        return [
                                                                            $operation->id => sprintf(
                                                                                "Line: %s, WS: %s, Desc: %s",
                                                                                $operation->productionLine->name ?? 'N/A',
                                                                                $operation->workstation->name ?? 'N/A',
                                                                                $operation->description ?? 'No description'
                                                                            )
                                                                        ];
                                                                    });
                                                            
                                                            default:
                                                                return [];
                                                        }
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->reactive(),
                                            ]),
                                        
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('add_operation')
                                                ->label('Add Operation to Performance')
                                                ->icon('heroicon-o-plus')
                                                ->action(function ($get, $set) {
                                                    $operationType = $get('operation_type');
                                                    $operationId = $get('selected_operation');
                                                    
                                                    if (!$operationType || !$operationId) {
                                                        return;
                                                    }
                                                    
                                                    $existingRecords = $get('performance_records') ?? [];
                                                    
                                                    $exists = collect($existingRecords)->contains(function ($record) use ($operationType, $operationId) {
                                                        return $record['type'] === $operationType && $record['id'] == $operationId;
                                                    });
                                                    
                                                    if ($exists) {
                                                        return;
                                                    }
                                                    
                                                    $operationDetails = [];
                                                    $assignedEmployees = [];
                                                    $assignedSupervisors = [];
                                                    
                                                    switch ($operationType) {
                                                        case 'assigned':
                                                            $operation = AssignDailyOperationLine::with([
                                                                'productionLine', 
                                                                'workstation', 
                                                                'operation',
                                                                'assignedEmployees.user', 
                                                                'assignedSupervisors.user'
                                                            ])->find($operationId);
                                                            
                                                            if ($operation) {
                                                                $operationDetails = [
                                                                    'type' => 'assigned',
                                                                    'id' => $operationId,
                                                                    'operation_name' => $operation->operation->description ?? 'N/A',
                                                                    'production_line' => $operation->productionLine->name ?? 'N/A',
                                                                    'workstation' => $operation->workstation->name ?? 'N/A',
                                                                    'machine_setup_time' => $operation->machine_setup_time ?? 0,
                                                                    'machine_run_time' => $operation->machine_run_time ?? 0,
                                                                    'labor_setup_time' => $operation->labor_setup_time ?? 0,
                                                                    'labor_run_time' => $operation->labor_run_time ?? 0,
                                                                    'target_duration' => $operation->target_duration ?? 'N/A',
                                                                    'target' => $operation->target ?? 0,
                                                                    'measurement_unit' => $operation->measurement_unit ?? 'N/A',
                                                                ];
                                                                
                                                                // Get assigned employees and supervisors
                                                                $assignedEmployees = $operation->assignedEmployees->pluck('id')->toArray();
                                                                $assignedSupervisors = $operation->assignedSupervisors->pluck('id')->toArray();
                                                            }
                                                            break;
                                                        
                                                        case 'um':
                                                            $operation = UMOperationLine::with([
                                                                'productionLine', 
                                                                'workstation', 
                                                                'operation',
                                                                'umOperationLineEmployees',
                                                                'umOperationLineSupervisors'
                                                            ])->find($operationId);
                                                            
                                                            if ($operation) {
                                                                $operationDetails = [
                                                                    'type' => 'um',
                                                                    'id' => $operationId,
                                                                    'operation_name' => $operation->operation->description ?? 'N/A',
                                                                    'production_line' => $operation->productionLine->name ?? 'N/A',
                                                                    'workstation' => $operation->workstation->name ?? 'N/A',
                                                                    'machine_setup_time' => $operation->machine_setup_time ?? 0,
                                                                    'machine_run_time' => $operation->machine_run_time ?? 0,
                                                                    'labor_setup_time' => $operation->labor_setup_time ?? 0,
                                                                    'labor_run_time' => $operation->labor_run_time ?? 0,
                                                                    'target_duration' => $operation->target_duration ?? 'N/A',
                                                                    'target' => $operation->target ?? 0,
                                                                    'measurement_unit' => $operation->measurement_unit ?? 'N/A',
                                                                ];
                                                                
                                                                // Get assigned employees and supervisors
                                                                $assignedEmployees = $operation->umOperationLineEmployees->pluck('id')->toArray();
                                                                $assignedSupervisors = $operation->umOperationLineSupervisors->pluck('id')->toArray();
                                                            }
                                                            break;
                                                        
                                                        case 'temp':
                                                            $operation = TemporaryOperation::with([
                                                                'productionLine', 
                                                                'workstation',
                                                                'temporaryOperationEmployees',
                                                                'temporaryOperationSupervisors'
                                                            ])->find($operationId);
                                                            
                                                            if ($operation) {
                                                                $operationDetails = [
                                                                    'type' => 'temp',
                                                                    'id' => $operationId,
                                                                    'operation_name' => $operation->description ?? 'No description',
                                                                    'production_line' => $operation->productionLine->name ?? 'N/A',
                                                                    'workstation' => $operation->workstation->name ?? 'N/A',
                                                                    'machine_setup_time' => $operation->machine_setup_time ?? 0,
                                                                    'machine_run_time' => $operation->machine_run_time ?? 0,
                                                                    'labor_setup_time' => $operation->labor_setup_time ?? 0,
                                                                    'labor_run_time' => $operation->labor_run_time ?? 0,
                                                                    'target_duration' => $operation->target_duration ?? 'N/A',
                                                                    'target' => $operation->target ?? 0,
                                                                    'measurement_unit' => $operation->measurement_unit ?? 'N/A',
                                                                ];
                                                                
                                                                // Get assigned employees and supervisors
                                                                $assignedEmployees = $operation->temporaryOperationEmployees->pluck('id')->toArray();
                                                                $assignedSupervisors = $operation->temporaryOperationSupervisors->pluck('id')->toArray();
                                                            }
                                                            break;
                                                    }
                                                    
                                                    if (!empty($operationDetails)) {
                                                        // Add assigned users to operation details
                                                        $operationDetails['assigned_employees'] = $assignedEmployees;
                                                        $operationDetails['assigned_supervisors'] = $assignedSupervisors;
                                                        
                                                        $existingRecords[] = $operationDetails;
                                                        $set('performance_records', $existingRecords);
                                                        $set('selected_operation', null);
                                                    }
                                                })
                                                ->disabled(function ($get) {
                                                    return !$get('selected_operation');
                                                }),
                                        ]),
                                    ]),
                                
                                Section::make('Selected Operations')
                                    ->schema([
                                        Repeater::make('performance_records')
                                            ->schema([
                                                Grid::make(4)
                                                    ->schema([                                                        
                                                        TextInput::make('operation_name')
                                                            ->label('Operation')
                                                            ->disabled(),
                                                        TextInput::make('production_line')
                                                            ->label('Production Line')
                                                            ->disabled(),
                                                        TextInput::make('workstation')
                                                            ->label('Workstation')
                                                            ->disabled(),
                                                        TextInput::make('machine_setup_time')
                                                            ->label('Machine Setup (min)')
                                                            ->disabled(),
                                                        TextInput::make('machine_run_time')
                                                            ->label('Machine Run (min)')
                                                            ->disabled(),
                                                        TextInput::make('labor_setup_time')
                                                            ->label('Labor Setup (min)')
                                                            ->disabled(),
                                                        TextInput::make('labor_run_time')
                                                            ->label('Labor Run (min)')
                                                            ->disabled(),
                                                        TextInput::make('target_duration')
                                                            ->label('Target Duration')
                                                            ->disabled(),
                                                        TextInput::make('target')
                                                            ->label('Target Quantity')
                                                            ->disabled(),
                                                        TextInput::make('measurement_unit')
                                                            ->label('Measurement Unit')
                                                            ->disabled(),
                                                        Hidden::make('type'),
                                                        Hidden::make('id'),
                                                        Hidden::make('assigned_employees'),
                                                        Hidden::make('assigned_supervisors'),
                                                    ]),
                                            ])
                                            ->columns(1)
                                            ->default([])
                                            ->disableItemCreation(),                                    
                                    ]),
                            ]),
                            
                        Tab::make('Employee Performance Records')
                            ->schema([
                                Section::make('Employee Performance')
                                    ->schema([
                                        Repeater::make('employee_performances')
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        Select::make('operation_id')
                                                            ->label('Operation')
                                                            ->options(function ($get) {
                                                                $performanceRecords = $get('../../performance_records') ?? [];
                                                                $options = [];
                                                                foreach ($performanceRecords as $index => $record) {
                                                                    $options[$index] = $record['operation_name'] . ' - ' . $record['production_line'] . ' - ' . $record['workstation'];
                                                                }
                                                                return $options;
                                                            })
                                                            ->required()
                                                            ->reactive()
                                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                                $set('employee_id', null);
                                                            }),
                                                        
                                                        Select::make('employee_id')
                                                            ->label('Employee')
                                                            ->options(function ($get, $set, $state) {
                                                                $operationIndex = $get('operation_id');
                                                                $performanceRecords = $get('../../performance_records') ?? [];
                                                                
                                                                if (!isset($performanceRecords[$operationIndex])) {
                                                                    return User::role('employee')->pluck('name', 'id');
                                                                }
                                                                
                                                                $operation = $performanceRecords[$operationIndex];
                                                                
                                                                // Get assigned employees if available
                                                                if (isset($operation['assigned_employees']) && !empty($operation['assigned_employees'])) {
                                                                    return User::whereIn('id', $operation['assigned_employees'])
                                                                        ->pluck('name', 'id');
                                                                }
                                                                
                                                                // Fallback to all employees if no assigned ones
                                                                return User::role('employee')->pluck('name', 'id');
                                                            })
                                                            ->required()
                                                            ->searchable(),
                                                        
                                                        Select::make('shift')
                                                            ->label('Shift')
                                                            ->options([
                                                                'morning' => 'Morning',
                                                                'afternoon' => 'Afternoon',
                                                                'evening' => 'Evening',
                                                                'night' => 'Night',
                                                            ])
                                                            ->required(),
                                                    ]),
                                                
                                                Grid::make(4)
                                                    ->schema([
                                                        TimePicker::make('start_time')
                                                            ->label('Start Time')
                                                            ->required()
                                                            ->seconds(false)
                                                            ->native(false),
                                                        
                                                        TimePicker::make('end_time')
                                                            ->label('End Time')
                                                            ->required()
                                                            ->seconds(false)
                                                            ->native(false),
                                                        
                                                        TextInput::make('actual_quantity')
                                                            ->label('Actual Quantity Produced')
                                                            ->numeric()
                                                            ->required()
                                                            ->default(0)
                                                            ->minValue(0),
                                                        
                                                        TextInput::make('quality_rating')
                                                            ->label('Quality Rating (1-10)')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->maxValue(10)
                                                            ->default(10),
                                                    ]),
                                                
                                                Grid::make(2)
                                                    ->schema([
                                                        Textarea::make('performance_notes')
                                                            ->label('Performance Notes')
                                                            ->rows(2)
                                                            ->placeholder('Any specific notes about employee performance...'),
                                                        
                                                        Textarea::make('issues_encountered')
                                                            ->label('Issues Encountered')
                                                            ->rows(2)
                                                            ->placeholder('Any problems or challenges faced...'),
                                                    ]),
                                            ])
                                            ->columns(1)
                                            ->addActionLabel('Add Employee Performance Record')
                                            ->default([])
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => 
                                                isset($state['employee_id']) && isset($state['operation_id']) 
                                                    ? User::find($state['employee_id'])?->name . ' - Operation #' . ($state['operation_id'] + 1)
                                                    : 'New Employee Performance'
                                            ),
                                    ]),
                            ]),
                            
                        Tab::make('Supervisor Performance Records')
                            ->schema([
                                Section::make('Supervisor Performance')
                                    ->schema([
                                        Repeater::make('supervisor_performances')
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        Select::make('operation_id')
                                                            ->label('Operation')
                                                            ->options(function ($get) {
                                                                $performanceRecords = $get('../../performance_records') ?? [];
                                                                $options = [];
                                                                foreach ($performanceRecords as $index => $record) {
                                                                    $options[$index] = $record['operation_name'] . ' - ' . $record['production_line'] . ' - ' . $record['workstation'];
                                                                }
                                                                return $options;
                                                            })
                                                            ->required()
                                                            ->reactive()
                                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                                $set('supervisor_id', null);
                                                            }),
                                                        
                                                        Select::make('supervisor_id')
                                                            ->label('Supervisor')
                                                            ->options(function ($get, $set, $state) {
                                                                $operationIndex = $get('operation_id');
                                                                $performanceRecords = $get('../../performance_records') ?? [];
                                                                
                                                                if (!isset($performanceRecords[$operationIndex])) {
                                                                    return User::role('supervisor')->pluck('name', 'id');
                                                                }
                                                                
                                                                $operation = $performanceRecords[$operationIndex];
                                                                
                                                                // Get assigned supervisors if available
                                                                if (isset($operation['assigned_supervisors']) && !empty($operation['assigned_supervisors'])) {
                                                                    return User::whereIn('id', $operation['assigned_supervisors'])
                                                                        ->pluck('name', 'id');
                                                                }
                                                                
                                                                // Fallback to all supervisors if no assigned ones
                                                                return User::role('supervisor')->pluck('name', 'id');
                                                            })
                                                            ->required()
                                                            ->searchable(),
                                                        
                                                        Select::make('shift')
                                                            ->label('Shift')
                                                            ->options([
                                                                'morning' => 'Morning',
                                                                'afternoon' => 'Afternoon',
                                                                'evening' => 'Evening',
                                                                'night' => 'Night',
                                                            ])
                                                            ->required(),
                                                    ]),
                                                
                                                Grid::make(4)
                                                    ->schema([
                                                        TimePicker::make('supervision_start_time')
                                                            ->label('Supervision Start Time')
                                                            ->required()
                                                            ->seconds(false)
                                                            ->native(false),
                                                        
                                                        TimePicker::make('supervision_end_time')
                                                            ->label('Supervision End Time')
                                                            ->required()
                                                            ->seconds(false)
                                                            ->native(false),
                                                        
                                                        TextInput::make('employees_supervised')
                                                            ->label('Number of Employees Supervised')
                                                            ->numeric()
                                                            ->required()
                                                            ->default(1)
                                                            ->minValue(1),
                                                        
                                                        TextInput::make('efficiency_rating')
                                                            ->label('Team Efficiency Rating (1-10)')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->maxValue(10)
                                                            ->default(8),
                                                    ]),
                                                
                                                Grid::make(3)
                                                    ->schema([
                                                        TextInput::make('problems_resolved')
                                                            ->label('Problems Resolved')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->minValue(0),
                                                        
                                                        TextInput::make('safety_incidents')
                                                            ->label('Safety Incidents')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->minValue(0),
                                                        
                                                        TextInput::make('quality_checks_performed')
                                                            ->label('Quality Checks Performed')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->minValue(0),
                                                    ]),
                                                
                                                Grid::make(2)
                                                    ->schema([
                                                        Textarea::make('supervision_notes')
                                                            ->label('Supervision Notes')
                                                            ->rows(2)
                                                            ->placeholder('Notes about team supervision and performance...'),
                                                        
                                                        Textarea::make('improvement_suggestions')
                                                            ->label('Improvement Suggestions')
                                                            ->rows(2)
                                                            ->placeholder('Suggestions for process improvements...'),
                                                    ]),
                                            ])
                                            ->columns(1)
                                            ->addActionLabel('Add Supervisor Performance Record')
                                            ->default([])
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => 
                                                isset($state['supervisor_id']) && isset($state['operation_id']) 
                                                    ? User::find($state['supervisor_id'])?->name . ' - Operation #' . ($state['operation_id'] + 1)
                                                    : 'New Supervisor Performance'
                                            ),
                                    ]),
                            ]),
                            
                        // ... (rest of your tabs remain the same)
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_type')->label('Order Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'customer_order' => 'Customer Order',
                        'sample_order' => 'Sample Order',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('order_id')->label('Order ID'),
                Tables\Columns\TextColumn::make('performances.*.operation')->label('Operation'),
                Tables\Columns\TextColumn::make('performances.*.actual_quantity')->label('Quantity'),
                Tables\Columns\TextColumn::make('performances.*.actual_time')->label('Time (min)'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnterPerformanceRecords::route('/'),
            'create' => Pages\CreateEnterPerformanceRecord::route('/create'),
            'edit' => Pages\EditEnterPerformanceRecord::route('/{record}/edit'),
        ];
    }
}