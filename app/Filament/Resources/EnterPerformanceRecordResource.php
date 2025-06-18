<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnterPerformanceRecordResource\Pages;
use App\Filament\Resources\EnterPerformanceRecordResource\RelationManagers;
use App\Models\EnterPerformanceRecord;
use App\Models\ProductionLine;
use App\Models\Workstation;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use App\Models\InventoryItem;
use App\Models\NonInventoryItem;
use App\Models\InventoryLocation;
use App\Models\Category; 
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns;
use Filament\Forms\Components\Tab;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Modal;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;


class EnterPerformanceRecordResource extends Resource
{
    protected static ?string $model = EnterPerformanceRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Enter Daily Operation Performance';
    protected static ?string $navigationGroup = 'Daily Production';


    public static function getAvailableLabels(int $modelId = null): array
    {
        if (!$modelId) return [];

        return \App\Models\AssignDailyOperationLabel::where('assign_daily_operation_id', $modelId)
            ->get()
            ->mapWithKeys(fn($label) => [
                $label->id => "{$label->cuttingLabel->quantity} | {$label->cuttingLabel->barcode_id}"
            ])->toArray();
    }

    public static function getLabelsInRange(array $allLabels, $fromId, $toId): array
    {
        $labelIds = array_keys($allLabels);
        $fromIndex = array_search($fromId, $labelIds);
        $toIndex = array_search($toId, $labelIds);
        
        if ($fromIndex === false || $toIndex === false) {
            return [];
        }
        
        // Ensure from is before to
        if ($fromIndex > $toIndex) {
            [$fromIndex, $toIndex] = [$toIndex, $fromIndex];
        }
        
        return array_slice($labelIds, $fromIndex, $toIndex - $fromIndex + 1);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Enter Operation')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Operation Selection')
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        DatePicker::make('operated_date')
                                            ->label('Operation Date')
                                            ->required()
                                            ->reactive()
                                            ->default(now())
                                            ->maxDate(now())
                                            ->columns(1)
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $set('operation_id', null);
                                                $set('order_type', null);
                                                $set('order_id', null);
                                                $set('operation_date', null);
                                                $set('machine_setup_time', null);
                                                $set('machine_run_time', null);
                                                $set('labor_setup_time', null);
                                                $set('labor_run_time', null);
                                                $set('target_duration', null);
                                                $set('target', null);
                                                $set('measurement_unit', null);
                                                $set('model_id', null);
                                                $set('employee_ids', null);
                                            }),
                                    ]),

                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        Select::make('operation_id')
                                            ->label('Operation')
                                            ->reactive()
                                            ->columns(1)
                                            ->required()
                                            ->searchable()
                                            ->options(function (callable $get) {
                                                $operatedDate = $get('operated_date');

                                                if (!$operatedDate) return [];

                                                return \App\Models\AssignDailyOperationLine::with(['assignDailyOperation'])
                                                    ->whereHas('assignDailyOperation', fn($q) => $q->whereDate('operation_date', $operatedDate))
                                                    ->get()
                                                    ->mapWithKeys(fn($line) => [
                                                        $line->id => "Assigned Line - {$line->id} | " . 
                                                                    ($line->assignDailyOperation ? 
                                                                        "{$line->assignDailyOperation->order_type} - {$line->assignDailyOperation->order_id}" : 
                                                                        'No Parent Operation')
                                                    ]);
                                                })
                                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                if (!$state) return;

                                                $model = \App\Models\AssignDailyOperationLine::with(['assignDailyOperation'])->find($state);

                                                if ($model) {
                                                    $set('model_id', $model->assignDailyOperation->id);
                                                    $set('order_type', $model->assignDailyOperation->order_type);
                                                    $set('order_id', $model->assignDailyOperation->order_id);
                                                    $set('operation_date', $model->assignDailyOperation->operation_date);
                                                    $set('machine_setup_time', $model->machine_setup_time ?? 0);
                                                    $set('machine_run_time', $model->machine_run_time ?? 0);
                                                    $set('labor_setup_time', $model->labor_setup_time ?? 0);
                                                    $set('labor_run_time', $model->labor_run_time ?? 0);
                                                    $set('target_duration', $model->target_duration ?? null);
                                                    $set('target', $model->target ?? null);
                                                    $set('measurement_unit', $model->measurement_unit ?? null);

                                                    // Fetch employee data and set employee IDs
                                                    $employees = \App\Models\AssignedEmployee::with('user')
                                                        ->where('assign_daily_operation_line_id', $state)
                                                        ->get();

                                                    if (!$employees->isEmpty()) {
                                                        $employeeDetails = $employees->map(function ($employee) {
                                                            return [
                                                                'user_id' => $employee->user_id,
                                                                'name' => $employee->user->name ?? 'N/A',
                                                            ];
                                                        })->toArray();

                                                        $set('employee_details', $employeeDetails);
                                                        $set('employee_ids', $employees->pluck('user_id')->implode(', '));
                                                    } else {
                                                        $set('employee_details', []);
                                                        $set('employee_ids', null); 
                                                    }

                                                    // Fetch production machine data 
                                                    $machines = \App\Models\AssignedProductionMachine::with('productionMachine')
                                                            ->where('assign_daily_operation_line_id', $state)
                                                            ->get();
                                                    
                                                    if (!$machines->isEmpty()) {
                                                        $machineDetails = $machines->map(function ($machine) {
                                                            return [
                                                                'id' => $machine->productionMachine->id ?? null,
                                                                'name' => $machine->productionMachine->name ?? 'Unnamed',
                                                            ];
                                                        })->toArray();
                                                        
                                                        $set('machines', $machineDetails);
                                                    } else {
                                                        $set('machines', []); 
                                                    }

                                                    // Fetch supervisor data 
                                                    $supervisors =  \App\Models\AssignedSupervisor::with('user')
                                                            ->where('assign_daily_operation_line_id', $state)
                                                            ->get();
                                                    
                                                    if (!$supervisors->isEmpty()) {
                                                        $supervisorDetails = $supervisors->map(function ($supervisor) {
                                                            return [
                                                                'user_id' => $supervisor->user_id,
                                                                'name' => $supervisor->user->name ?? 'N/A',
                                                            ];
                                                        })->toArray();

                                                        $set('supervisor_details', $supervisorDetails);
                                                        $set('supervisor_ids', $supervisors->pluck('user_id')->implode(', '));
                                                    } else {
                                                        $set('supervisor_details', []);
                                                        $set('supervisor_details', []); 
                                                    }

                                                    // Fetch third party service data and their processes
                                                    $services =  \App\Models\AssignedThirdPartyService::with('thirdPartyService.processes')
                                                            ->where('assign_daily_operation_line_id', $state)
                                                            ->get();

                                                    if (!$services->isEmpty()) {
                                                        $serviceDetails = $services->map(function ($service) {
                                                            $processes = $service->thirdPartyService->processes->map(function ($process) {
                                                                // Get supplier name (assuming there's a relationship)
                                                                $supplierName = $process->supplier->name ?? 'Unknown Supplier';
                                                                
                                                                return [
                                                                    'process_id' => $process->id,
                                                                    'description' => $process->description,
                                                                    'related_table' => $process->related_table,
                                                                    'related_record_id' => $process->related_record_id,
                                                                    'unit_of_measurement' => $process->unit_of_measurement,
                                                                    'amount' => $process->amount,
                                                                    'unit_rate' => $process->unit_rate,
                                                                    'used_amount' => 0, 
                                                                    'total' => 0, 
                                                                ];
                                                            })->toArray();

                                                            return [
                                                                'id' => $service->thirdPartyService->id ?? null,
                                                                'name' => $service->thirdPartyService->name ?? 'Unnamed',
                                                                'supplier_id' => $service->thirdPartyService->supplier->supplier_id ?? 'Unnamed',
                                                                'supplier_name' => $service->thirdPartyService->supplier->name ?? 'Unknown Supplier',
                                                                'processes' => $processes,
                                                            ];
                                                        })->toArray();

                                                        $set('services', $serviceDetails);
                                                    } else {
                                                        $set('services', []); 
                                                    }
                                                }
                                            }),

                                        TextInput::make('model_id')
                                            ->label('model id')
                                            ->disabled()
                                            ->columns(1),
                                            
                                        TextInput::make('order_type')
                                            ->label('Order Type')
                                            ->disabled()
                                            ->columns(1),

                                        TextInput::make('order_id')
                                            ->label('Order ID')
                                            ->disabled()
                                            ->columns(1),

                                        TextInput::make('operation_date')
                                            ->label('Operation Date')
                                            ->disabled()
                                            ->columns(1),

                                        CheckboxList::make('selected_labels')
                                            ->label('Available Labels')
                                            ->options(function (callable $get) {
                                                return self::getAvailableLabels($get('model_id'));
                                            }),
                                    ]),
                            ]),

                        Tabs\Tab::make('Production Data')
                            ->visible(fn (callable $get) => $get('operation_id'))
                            ->schema([
                                Section::make('Pre-Defined Performance Values')
                                    ->columns(4)
                                    ->schema([
                                        TextInput::make('machine_setup_time')
                                            ->label('Machine Setup Time')
                                            ->disabled()
                                            ->reactive()
                                            ->columns(2),

                                        TextInput::make('machine_run_time')
                                            ->label('Machine Run Time')
                                            ->disabled()
                                            ->reactive()
                                            ->columns(2),

                                        TextInput::make('labor_setup_time')
                                            ->label('Labor Setup Time')
                                            ->disabled()
                                            ->reactive()
                                            ->columns(2),

                                        TextInput::make('labor_run_time')
                                            ->label('Labor Run Time')
                                            ->disabled()
                                            ->reactive()
                                            ->columns(2),

                                        TextInput::make('target_duration')
                                            ->label('Target Duration')
                                            ->disabled()
                                            ->columns(1),

                                        TextInput::make('target')
                                            ->label('Target')
                                            ->disabled()
                                            ->columns(2),

                                        TextInput::make('measurement_unit')
                                            ->label('Measurement Unit')
                                            ->disabled()
                                            ->columns(1),
                                    ]),

                                Section::make('Actual Performance Values')
                                    ->columns(4)
                                    ->schema([
                                        TextInput::make('actual_machine_setup_time')
                                            ->label('Actual Machine Setup Time')
                                            ->default(0)
                                            ->numeric()
                                            ->required()
                                            ->reactive()
                                            ->columns(2),

                                        TextInput::make('actual_machine_run_time')
                                            ->label('Actual Machine Run Time')
                                            ->default(0)
                                            ->numeric()
                                            ->required()
                                            ->reactive()
                                            ->columns(2),

                                        TextInput::make('actual_labor_setup_time')
                                            ->label('Actual Labor Setup Time')
                                            ->default(0)
                                            ->numeric()
                                            ->required()
                                            ->reactive()
                                            ->columns(2),

                                        TextInput::make('actual_labor_run_time')
                                            ->label('Actual Labor Run Time')
                                            ->default(0)
                                            ->numeric()
                                            ->required()
                                            ->reactive()
                                            ->columns(2),                             
                                    ]),

                                Section::make('Operated Time Frame')
                                    ->columns(4)
                                    ->schema([
                                        Section::make()
                                            ->columns(4)
                                            ->schema([
                                                TimePicker::make('operated_time_from')
                                                    ->label('From')
                                                    ->required()
                                                    ->withoutSeconds()
                                                    ->reactive()
                                                    ->afterStateUpdated(function (callable $get, callable $set) {
                                                        $from = $get('operated_time_from');
                                                        $to = $get('operated_time_to');
                                                        $now = now()->format('H:i');

                                                        if ($from && $from > $now) {
                                                            Notification::make()
                                                                ->title('Invalid time')
                                                                ->body('You cannot select a future time.')
                                                                ->danger()
                                                                ->send();
                                                            $set('operated_time_from', null);
                                                            return;
                                                        }

                                                        if ($from && $to) {
                                                            $fromTime = \Carbon\Carbon::createFromFormat('H:i', $from);
                                                            $toTime = \Carbon\Carbon::createFromFormat('H:i', $to);

                                                            if ($toTime->lt($fromTime)) {
                                                                $toTime->addDay();
                                                            }

                                                            $minutes = $toTime->diffInMinutes($fromTime);
                                                            $hours = floor($minutes / 60);
                                                            $remainingMinutes = $minutes % 60;

                                                            $durationText = ($hours ? "{$hours}h " : '') . "{$remainingMinutes}m";
                                                            $set('operated_time_duration', trim($durationText));
                                                        } else {
                                                            $set('operated_time_duration', null);
                                                        }
                                                    })
                                                    ->columnSpan(1),

                                                TimePicker::make('operated_time_to')
                                                    ->label('To')
                                                    ->required()
                                                    ->withoutSeconds()
                                                    ->reactive()
                                                    ->afterStateUpdated(function (callable $get, callable $set) {
                                                        $from = $get('operated_time_from');
                                                        $to = $get('operated_time_to');
                                                        $now = now()->format('H:i');

                                                        if ($to && $to > $now) {
                                                            Notification::make()
                                                                ->title('Invalid time')
                                                                ->body('You cannot select a future time.')
                                                                ->danger()
                                                                ->send();
                                                            $set('operated_time_to', null);
                                                            return;
                                                        }

                                                        if ($from && $to) {
                                                            $fromTime = \Carbon\Carbon::createFromFormat('H:i', $from);
                                                            $toTime = \Carbon\Carbon::createFromFormat('H:i', $to);

                                                            if ($toTime->lt($fromTime)) {
                                                                $toTime->addDay();
                                                            }

                                                            $minutes = $toTime->diffInMinutes($fromTime);
                                                            $hours = floor($minutes / 60);
                                                            $remainingMinutes = $minutes % 60;

                                                            $durationText = ($hours ? "{$hours}h " : '') . "{$remainingMinutes}m";
                                                            $set('operated_time_duration', trim($durationText));
                                                        } else {
                                                            $set('operated_time_duration', null);
                                                        }
                                                    })
                                                    ->columnSpan(1),

                                                TextInput::make('operated_time_duration')
                                                    ->label('Duration (hh:mm)')
                                                    ->disabled()
                                                    ->columnSpan(2),
                                            ]),
                                    ])
                                    ->columns(1)
                            ]),

                        Tabs\Tab::make('Employees')
                        ->lazy()
                        ->visible(fn (callable $get) => $get('operation_id') && !empty($get('employee_details')))
                        ->schema([
                            Section::make('Assigned Employees Details')
                                ->columns(1)
                                ->schema([
                                    Repeater::make('employee_details')
                                        ->columns(4)
                                        ->disableItemCreation()
                                        ->disableItemDeletion()
                                        ->schema([
                                            TextInput::make('user_id')
                                                ->label('User ID')
                                                ->columns(1)
                                                ->disabled(),

                                            TextInput::make('name')
                                                ->label('Name')
                                                ->columns(1)
                                                ->disabled(),

                                            TextInput::make('emp_downtime')
                                                ->label('Emp: Downtime (min)')
                                                ->reactive()
                                                ->live()
                                                ->columns(1),

                                            Section::make('Select Labels_e')
                                                ->label('Produced Labels - Employee')
                                                ->collapsible()
                                                ->schema([
                                                    Grid::make(3)
                                                        ->schema([
                                                            Placeholder::make('selected_labels_count_e')
                                                                ->label('Selected Labels Count')
                                                                ->content(function (callable $get) {
                                                                    $labels = $get('selected_labels_e');
                                                                    return (is_array($labels) ? count($labels) : 0) . ' label(s) selected';
                                                                })
                                                                ->reactive(),

                                                            Checkbox::make('select_all_labels_e')
                                                                ->label('Select All Labels')
                                                                ->default(false)
                                                                ->reactive()
                                                                ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                                                    $labels = self::getAvailableLabels($get('../../model_id'));
                                                                    $set('selected_labels_e', $state ? array_keys($labels) : []);
                                                                    
                                                                    // Reset range selection when select all is used
                                                                    if ($state) {
                                                                        $set('range_from_label_e', null);
                                                                        $set('range_to_label_e', null);
                                                                    }
                                                                }),

                                                            Actions::make([
                                                                Action::make('clear_selection')
                                                                    ->label('Clear All')
                                                                    ->color('danger')
                                                                    ->size('sm')
                                                                    ->action(function (callable $set) {
                                                                        $set('selected_labels_e', []);
                                                                        $set('select_all_labels_e', false);
                                                                        $set('range_from_label_e', null);
                                                                        $set('range_to_label_e', null);
                                                                    })
                                                            ])
                                                        ]),

                                                    Fieldset::make('Label Selection')
                                                    ->schema([
                                                        Grid::make(12)
                                                            ->schema([
                                                                Actions::make([
                                                                    Action::make('select_first_10')
                                                                        ->label('First 10')
                                                                        ->size('sm')
                                                                        ->color('gray')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $labels = array_keys(self::getAvailableLabels($get('../../model_id')));
                                                                            $selected = array_slice($labels, 0, 10);
                                                                            $currentSelected = $get('selected_labels_e') ?: [];
                                                                            $set('selected_labels_e', array_unique(array_merge($currentSelected, $selected)));
                                                                        }),
                                                                ])->columns(3), 

                                                                Actions::make([
                                                                    Action::make('select_last_10')
                                                                        ->label('Last 10')
                                                                        ->size('sm')
                                                                        ->color('gray')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $labels = array_keys(self::getAvailableLabels($get('../../model_id')));
                                                                            $selected = array_slice($labels, -10);
                                                                            $currentSelected = $get('selected_labels_e') ?: [];
                                                                            $set('selected_labels_e', array_unique(array_merge($currentSelected, $selected)));
                                                                        }),
                                                                ])->columns(3), 

                                                                Actions::make([
                                                                    Action::make('select_even')
                                                                        ->label('Even Positions')
                                                                        ->size('sm')
                                                                        ->color('gray')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $labels = array_keys(self::getAvailableLabels($get('../../model_id')));
                                                                            $selected = [];
                                                                            foreach ($labels as $index => $labelId) {
                                                                                if ($index % 2 === 0) {
                                                                                    $selected[] = $labelId;
                                                                                }
                                                                            }
                                                                            $currentSelected = $get('selected_labels_e') ?: [];
                                                                            $set('selected_labels_e', array_unique(array_merge($currentSelected, $selected)));
                                                                        }),
                                                                ])->columns(3), 

                                                                Actions::make([
                                                                    Action::make('select_odd')
                                                                        ->label('Odd Positions')
                                                                        ->size('sm')
                                                                        ->color('gray')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $labels = array_keys(self::getAvailableLabels($get('../../model_id')));
                                                                            $selected = [];
                                                                            foreach ($labels as $index => $labelId) {
                                                                                if ($index % 2 === 1) {
                                                                                    $selected[] = $labelId;
                                                                                }
                                                                            }
                                                                            $currentSelected = $get('selected_labels_e') ?: [];
                                                                            $set('selected_labels_e', array_unique(array_merge($currentSelected, $selected)));
                                                                        }),
                                                                ])->columns(3), 
                                                            ]),


                                                    Grid::make(4)
                                                        ->schema([
                                                            TextInput::make('range_start_position')
                                                                ->label('Start Position')
                                                                ->numeric()
                                                                ->minValue(1)
                                                                ->placeholder('1'),

                                                            TextInput::make('range_end_position')
                                                                ->label('End Position')
                                                                ->numeric()
                                                                ->minValue(fn (callable $get) => $get('range_start_position') ?: 1)
                                                                ->placeholder('10'),

                                                            Actions::make([
                                                                Action::make('select_numeric_range')
                                                                    ->label('Select by Position')
                                                                    ->color('info')
                                                                    ->size('sm')
                                                                    ->action(function (callable $set, callable $get) {
                                                                        $start = (int) $get('range_start_position') - 1; 
                                                                        $end = (int) $get('range_end_position');
                                                                        $labels = array_keys(self::getAvailableLabels($get('../../model_id')));
                                                                        
                                                                        if ($start >= 0 && $end > $start && $start < count($labels)) {
                                                                            $selected = array_slice($labels, $start, $end - $start);
                                                                            $currentSelected = $get('selected_labels_e') ?: [];
                                                                            $set('selected_labels_e', array_unique(array_merge($currentSelected, $selected)));
                                                                        }
                                                                    })
                                                            ])
                                                        ]),
                                                    ]),

                                                    // Label Picker
                                                    Grid::make(1)
                                                        ->schema([
                                                            CheckboxList::make('selected_labels_e')
                                                                ->label('Available Labels')
                                                                ->options(fn (callable $get) => self::getAvailableLabels($get('../../model_id')))
                                                                ->columns(4)
                                                                ->searchable()
                                                                ->live()
                                                                ->reactive()
                                                                ->required()
                                                                ->default([])
                                                                ->dehydrated()
                                                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                    $set('selected_labels_e', is_array($state) ? $state : []);
                                                                    
                                                                    $allLabels = self::getAvailableLabels($get('../../model_id'));
                                                                    $selectedCount = is_array($state) ? count($state) : 0;
                                                                    $set('select_all_labels_e', $selectedCount === count($allLabels));
                                                                })
                                                                ->bulkToggleable(false)
                                                        ])
                                                ]),
                                        ]),
                                ]),
                            ]),
                        
                        Tabs\Tab::make('Machines')
                            ->visible(fn (callable $get) => $get('operation_id') && !empty($get('machines')))
                            ->schema([
                                Section::make('Assigned Machines Details')
                                    ->columns(1)
                                    ->schema([
                                        Repeater::make('machines')
                                            ->columns(4)
                                            ->disableItemCreation()
                                            ->disableItemDeletion()
                                            ->schema([
                                                TextInput::make('id')->label('Machine ID')->columns(1)->disabled(),
                                                TextInput::make('name')->label('Name')->columns(1)->disabled(),

                                                Section::make('Select Labels_m')
                                                    ->label('Produced Labels - Machine')
                                                    ->collapsible()
                                                    ->schema([
                                                        Grid::make(3)
                                                            ->schema([
                                                                Placeholder::make('selected_labels_count_m')
                                                                    ->label('Selected Labels Count')
                                                                    ->content(function (callable $get) {
                                                                        $labels = $get('selected_labels_m');
                                                                        return (is_array($labels) ? count($labels) : 0) . ' label(s) selected';
                                                                    })
                                                                    ->reactive(),

                                                                Checkbox::make('select_all_labels_m')
                                                                    ->label('Select All Labels')
                                                                    ->default(false)
                                                                    ->reactive()
                                                                    ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                                                        $labels = self::getAvailableLabels($get('../../model_id'));
                                                                        $set('selected_labels_m', $state ? array_keys($labels) : []);
                                                                        
                                                                        // Reset range selection when select all is used
                                                                        if ($state) {
                                                                            $set('range_from_label_m', null);
                                                                            $set('range_to_label_m', null);
                                                                        }
                                                                    }),

                                                                Actions::make([
                                                                    Action::make('clear_selection')
                                                                        ->label('Clear All')
                                                                        ->color('danger')
                                                                        ->size('sm')
                                                                        ->action(function (callable $set) {
                                                                            $set('selected_labels_m', []);
                                                                            $set('select_all_labels_m', false);
                                                                            $set('range_from_label_m', null);
                                                                            $set('range_to_label_m', null);
                                                                        })
                                                                ])
                                                            ]),

                                                        Fieldset::make('Label Selection')
                                                        ->schema([
                                                            Grid::make(12)
                                                                ->schema([
                                                                    Actions::make([
                                                                        Action::make('select_first_10')
                                                                            ->label('First 10')
                                                                            ->size('sm')
                                                                            ->color('gray')
                                                                            ->action(function (callable $set, callable $get) {
                                                                                $labels = array_keys(self::getAvailableLabels($get('../../model_id')));
                                                                                $selected = array_slice($labels, 0, 10);
                                                                                $currentSelected = $get('selected_labels_m') ?: [];
                                                                                $set('selected_labels_m', array_unique(array_merge($currentSelected, $selected)));
                                                                            }),
                                                                    ])->columns(3), 

                                                                    Actions::make([
                                                                        Action::make('select_last_10')
                                                                            ->label('Last 10')
                                                                            ->size('sm')
                                                                            ->color('gray')
                                                                            ->action(function (callable $set, callable $get) {
                                                                                $labels = array_keys(self::getAvailableLabels($get('../../model_id')));
                                                                                $selected = array_slice($labels, -10);
                                                                                $currentSelected = $get('selected_labels_m') ?: [];
                                                                                $set('selected_labels_m', array_unique(array_merge($currentSelected, $selected)));
                                                                            }),
                                                                    ])->columns(3), 

                                                                    Actions::make([
                                                                        Action::make('select_even')
                                                                            ->label('Even Positions')
                                                                            ->size('sm')
                                                                            ->color('gray')
                                                                            ->action(function (callable $set, callable $get) {
                                                                                $labels = array_keys(self::getAvailableLabels($get('../../model_id')));
                                                                                $selected = [];
                                                                                foreach ($labels as $index => $labelId) {
                                                                                    if ($index % 2 === 0) {
                                                                                        $selected[] = $labelId;
                                                                                    }
                                                                                }
                                                                                $currentSelected = $get('selected_labels_m') ?: [];
                                                                                $set('selected_labels_m', array_unique(array_merge($currentSelected, $selected)));
                                                                            }),
                                                                    ])->columns(3), 

                                                                    Actions::make([
                                                                        Action::make('select_odd')
                                                                            ->label('Odd Positions')
                                                                            ->size('sm')
                                                                            ->color('gray')
                                                                            ->action(function (callable $set, callable $get) {
                                                                                $labels = array_keys(self::getAvailableLabels($get('../../model_id')));
                                                                                $selected = [];
                                                                                foreach ($labels as $index => $labelId) {
                                                                                    if ($index % 2 === 1) {
                                                                                        $selected[] = $labelId;
                                                                                    }
                                                                                }
                                                                                $currentSelected = $get('selected_labels_m') ?: [];
                                                                                $set('selected_labels_m', array_unique(array_merge($currentSelected, $selected)));
                                                                            }),
                                                                    ])->columns(3), 
                                                                ]),


                                                        Grid::make(4)
                                                            ->schema([
                                                                TextInput::make('range_start_position')
                                                                    ->label('Start Position')
                                                                    ->numeric()
                                                                    ->minValue(1)
                                                                    ->placeholder('1'),

                                                                TextInput::make('range_end_position')
                                                                    ->label('End Position')
                                                                    ->numeric()
                                                                    ->minValue(fn (callable $get) => $get('range_start_position') ?: 1)
                                                                    ->placeholder('10'),

                                                                Actions::make([
                                                                    Action::make('select_numeric_range')
                                                                        ->label('Select by Position')
                                                                        ->color('info')
                                                                        ->size('sm')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $start = (int) $get('range_start_position') - 1; 
                                                                            $end = (int) $get('range_end_position');
                                                                            $labels = array_keys(self::getAvailableLabels($get('../../model_id')));
                                                                            
                                                                            if ($start >= 0 && $end > $start && $start < count($labels)) {
                                                                                $selected = array_slice($labels, $start, $end - $start);
                                                                                $currentSelected = $get('selected_labels_m') ?: [];
                                                                                $set('selected_labels_m', array_unique(array_merge($currentSelected, $selected)));
                                                                            }
                                                                        })
                                                                ])
                                                            ]),
                                                        ]),

                                                        // Label Picker
                                                        Grid::make(1)
                                                            ->schema([
                                                                CheckboxList::make('selected_labels_m')
                                                                    ->label('Available Labels')
                                                                    ->options(fn (callable $get) => self::getAvailableLabels($get('../../model_id')))
                                                                    ->columns(4)
                                                                    ->searchable()
                                                                    ->live()
                                                                    ->required()
                                                                    ->reactive()
                                                                    ->default([])
                                                                    ->dehydrated()
                                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                        $set('selected_labels_m', is_array($state) ? $state : []);
                                                                        
                                                                        $allLabels = self::getAvailableLabels($get('../../model_id'));
                                                                        $selectedCount = is_array($state) ? count($state) : 0;
                                                                        $set('select_all_labels_m', $selectedCount === count($allLabels));
                                                                    })
                                                                    ->bulkToggleable(false)
                                                            ])
                                                    ]),
                                                
                                                TextInput::make('machine_downtime')->label('Downtime (min)')->numeric()->reactive()->live()->columns(1),
                                                TextArea::make('machine_notes')->label('Notes (Machines)')->columns(4),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Summary')
                                    ->schema([              
                                        Placeholder::make('machine_total_downtime')
                                            ->label('Machine: Total Downtime (min)')
                                            ->content(function (callable $get, callable $set) {
                                                $details = $get('machines') ?? [];
                                                $total = collect($details)->sum('machine_downtime') ?: 0;
                                                $set('machine_total_downtime', $total); 
                                                return $total;
                                            })
                                            ->reactive()
                                            ->live(),

                                        Hidden::make('machine_total_downtime')
                                            ->dehydrated(),
                                    ])
                            ]),

                        Tabs\Tab::make('Supervisors')
                            ->visible(fn (callable $get) => $get('operation_id') && !empty($get('supervisor_details')))
                            ->schema([
                                Section::make('Assigned Supervisor Details')
                                    ->columns(1)
                                    ->schema([
                                        Repeater::make('supervisor_details')
                                            ->columns(4)
                                            ->disableItemCreation()
                                            ->disableItemDeletion()
                                            ->schema([
                                                TextInput::make('user_id')->label('User ID')->columns(1)->disabled(),
                                                TextInput::make('name')->label('Name')->columns(1)->disabled(),
                                                
                                                TextInput::make('acc_quantity')
                                                    ->label('Accepted Quantity')
                                                    ->numeric()
                                                    ->required()
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        $rejected = (int) $get('rej_quantity');
                                                        $set('sup_quantity', $state + $rejected);
                                                    })
                                                    ->columns(1),

                                                TextInput::make('rej_quantity')
                                                    ->label('Rejected Quantity')
                                                    ->numeric()
                                                    ->required()
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        $accepted = (int) $get('acc_quantity');
                                                        $set('sup_quantity', $state + $accepted);
                                                    })
                                                    ->columns(1),

                                                TextInput::make('sup_quantity')
                                                    ->label('Supervisored Quantity')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->columns(1),

                                                TextInput::make('sup_downtime')->label('Supervisor : Downtime (min)')->numeric()->reactive()->live()->columns(1),
                                                TextArea::make('sup_notes')->label('Special Notes')->columns(4),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                                    Section::make('Summary')
                                        ->schema([
                                            Placeholder::make('total_sup_quantity')
                                            ->label('Total Supervisored Quantity')
                                            ->content(function (callable $get, callable $set) {
                                                $details = $get('supervisor_details') ?? [];
                                                $total = collect($details)->sum('sup_quantity') ?: 0;
                                                $set('total_sup_quantity', $total); 
                                                return $total;
                                            })
                                            ->reactive()
                                            ->live(),

                                        Placeholder::make('total_acc_quantity')
                                            ->label('Total Accepted Quantity')
                                            ->content(function (callable $get, callable $set) {
                                                $details = $get('supervisor_details') ?? [];
                                                $total = collect($details)->sum('acc_quantity') ?: 0;
                                                $set('total_acc_quantity', $total); 
                                                return $total;
                                            })
                                            ->reactive()
                                            ->live(),

                                        Placeholder::make('total_rej_quantity')
                                            ->label('Total Rejected Quantity')
                                            ->content(function (callable $get, callable $set) {
                                                $details = $get('supervisor_details') ?? [];
                                                $total = collect($details)->sum('rej_quantity') ?: 0;
                                                $set('total_rej_quantity', $total); 
                                                return $total;
                                            })
                                            ->reactive()
                                            ->live(),
                                        ])
                            ]),

                        Tabs\Tab::make('Third-Party Services')
                            ->visible(fn (callable $get) => $get('operation_id') && !empty($get('services')))
                            ->schema([
                                Section::make('Third-Party Service Details')
                                    ->columns(1)
                                    ->schema([
                                        Repeater::make('services')
                                            ->columns(4)
                                            ->disableItemCreation()
                                            ->disableItemDeletion()
                                            ->schema([
                                                TextInput::make('id')->label('Service ID')->columns(1)->disabled(),
                                                TextInput::make('name')->label('Name')->columns(1)->disabled(),
                                                TextInput::make('supplier_id')->label('Supplier ID')->disabled()->columns(1),
                                                TextInput::make('supplier_name')->label('Supplier Name')->disabled()->columns(2),
                                                
                                                // Processes repeater for each service
                                                Repeater::make('processes')
                                                    ->label('Service Processes')
                                                    ->columns(6)
                                                    ->disableItemCreation()
                                                    ->disableItemDeletion()
                                                    ->schema([
                                                        TextInput::make('process_id')->label('Process ID')->disabled()->columns(1),
                                                        TextInput::make('description')->label('Description')->disabled()->columns(2),
                                                        TextInput::make('related_table')->label('Related Table')->disabled()->columns(1),
                                                        TextInput::make('related_record_id')->label('Related Record ID')->disabled()->columns(1),
                                                        TextInput::make('unit_of_measurement')->label('UOM')->disabled()->columns(1),
                                                        TextInput::make('amount')->label('Available Amount')->numeric()->disabled()->columns(1),
                                                        TextInput::make('unit_rate')->label('Unit Rate')->numeric()->disabled()->columns(1),

                                                        TextInput::make('used_amount')
                                                            ->label('Used Amount')
                                                            ->numeric()
                                                            ->reactive()
                                                            ->required()
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, $set, $get) {
                                                                $available = (float) ($get('amount') ?? 0);
                                                                $unitRate = (float) ($get('unit_rate') ?? 0);

                                                                if ($state > $available) {
                                                                    $set('used_amount', null);
                                                                    $set('total', null);

                                                                    \Filament\Notifications\Notification::make()
                                                                        ->title('Used amount exceeds available amount')
                                                                        ->warning()
                                                                        ->body("Used amount ({$state}) must not exceed available amount ({$available}).")
                                                                        ->send();

                                                                    return;
                                                                }

                                                                $set('total', $state * $unitRate);
                                                            })
                                                            ->columns(1),

                                                        TextInput::make('total')
                                                            ->label('Total Cost')
                                                            ->disabled()
                                                            ->numeric()
                                                            ->columns(1),
                                                    ])
                                                    ->columnSpanFull(),
                                            ])
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Summary')
                                    ->schema([
                                    Placeholder::make('process_total_cost')
                                            ->label('Total Process Cost')
                                            ->content(function (callable $get, callable $set) {
                                                $services = $get('services') ?? [];
                                                $total = 0;

                                                foreach ($services as $service) {
                                                    foreach ($service['processes'] ?? [] as $process) {
                                                        $processTotal = is_numeric($process['total'] ?? null) ? (float) $process['total'] : 0;
                                                        $total += $processTotal;
                                                    }
                                                }

                                                $set('process_total_cost', $total);

                                                return number_format($total, 2);
                                            })
                                            ->reactive()
                                            ->live(),
                                    ])
                            ]),

                        Tabs\Tab::make('Summary of Production')
                            ->visible(fn (callable $get) => $get('operation_id'))
                            ->schema([
                                //  Section 2: Waste
                                Section::make('Waste Recording')
                                    ->schema([
                                        Repeater::make('inv_waste_products')
                                            ->label('Inventory Waste Products')
                                            ->columns(7)
                                            ->schema([
                                                TextInput::make('waste')
                                                    ->label('Waste')
                                                    ->numeric()
                                                    ->reactive()
                                                    ->columnSpan(2),

                                                Select::make('waste_measurement_unit')
                                                    ->label('UOM')
                                                    ->options([
                                                        'pcs' => 'Pieces',
                                                        'kgs' => 'Kilograms',
                                                        'liters' => 'Liters',
                                                        'minutes' => 'Minutes',
                                                        'hours' => 'Hours',
                                                    ])
                                                    ->required(fn (callable $get) => $get('waste') !== null && $get('waste') !== '')
                                                    ->columnSpan(1),

                                                Select::make('waste_item_id')
                                                    ->label('Waste Item')
                                                    ->searchable()
                                                    ->options(function () {
                                                        return InventoryItem::where('category', 'Waste Item')
                                                            ->orderBy('item_code')
                                                            ->get()
                                                            ->mapWithKeys(fn($item) => [
                                                                $item->id => "ID - {$item->id} | Item Code - {$item->item_code} | Name - {$item->name}"
                                                            ])
                                                            ->toArray();
                                                    })
                                                    ->required(fn (callable $get) => $get('waste') !== null && $get('waste') !== '')
                                                    ->columnSpan(2),

                                                Select::make('waste_location_id')
                                                    ->label('Waste Item Location')
                                                    ->searchable()
                                                    ->options(function () {
                                                        return InventoryLocation::where('location_type', 'picking')
                                                            ->orderBy('name')
                                                            ->get()
                                                            ->mapWithKeys(fn($location) => [
                                                                $location->id => "ID - {$location->id} | Name - {$location->name}"
                                                            ])
                                                            ->toArray();
                                                    })
                                                    ->required(fn (callable $get) => $get('waste') !== null && $get('waste') !== '')
                                                    ->columnSpan(2),
                                            ])
                                            ->createItemButtonLabel('Add Inventory Waste Product')
                                            ->columnSpan('full'),

                                            Repeater::make('non_inv_waste_products')
                                                ->label('Non-Inventory Waste Products')
                                                ->columns(6)
                                                ->schema([
                                                    TextInput::make('amount')
                                                        ->label('Amount')
                                                        ->numeric()
                                                        ->reactive()
                                                        ->columnSpan(2),

                                                    Select::make('item_id')
                                                        ->label('Non-Inventory Waste Item')
                                                        ->searchable()
                                                        ->options(function () {
                                                            return \App\Models\NonInventoryItem::orderBy('name')
                                                                ->get()
                                                                ->mapWithKeys(fn($item) => [
                                                                    $item->id => "ID - {$item->id} | Name - {$item->name}"
                                                                ])
                                                                ->toArray();
                                                        })
                                                        ->required(fn (callable $get) => $get('amount') !== null && $get('amount') !== '')
                                                        ->columnSpan(3),

                                                    Select::make('unit')
                                                        ->label('Unit')
                                                        ->options([
                                                            'pcs' => 'Pieces',
                                                            'kgs' => 'Kilograms',
                                                            'liters' => 'Liters',
                                                            'minutes' => 'Minutes',
                                                            'hours' => 'Hours',
                                                        ])
                                                        ->required(fn (callable $get) => $get('amount') !== null && $get('amount') !== '')
                                                        ->columnSpan(1),
                                                ])
                                                ->createItemButtonLabel('Add Non-Inventory Waste Product')
                                                ->columnSpan('full'),
                                                        
                                        ]),

                                    //  Section 3: By Products
                                    Section::make('By Products')
                                        ->schema([
                                            Repeater::make('by_products')
                                                ->label('By Products')
                                                ->columns(7)
                                                ->schema([
                                                    TextInput::make('amount')
                                                        ->label('Amount')
                                                        ->numeric()
                                                        ->reactive()
                                                        ->required(fn (callable $get) => $get('amount') !== null && $get('amount') !== '')
                                                        ->columnSpan(2),

                                                    Select::make('item_id')
                                                        ->label('Item')
                                                        ->searchable()
                                                        ->options(function () {
                                                            return InventoryItem::where('category', 'By Products')
                                                                ->orderBy('item_code')
                                                                ->get()
                                                                ->mapWithKeys(fn($item) => [
                                                                    $item->id => "ID - {$item->id} | Item Code - {$item->item_code} | Name - {$item->name}"
                                                                ])
                                                                ->toArray();
                                                        })
                                                        ->required(fn (callable $get) => $get('amount') !== null && $get('amount') !== '')
                                                        ->columnSpan(2),

                                                    Select::make('by_location_id')
                                                        ->label('By Product Location')
                                                        ->searchable()
                                                        ->options(function () {
                                                            return InventoryLocation::where('location_type', 'picking')
                                                                ->orderBy('name') 
                                                                ->get()
                                                                ->mapWithKeys(fn($location) => [
                                                                    $location->id => "ID - {$location->id} | Name - {$location->name}"
                                                                ])
                                                                ->toArray();
                                                        })
                                                        ->required(fn (callable $get) => $get('amount') !== null && $get('amount') !== '')
                                                        ->columnSpan(2),

                                                    Select::make('measurement_unit')
                                                        ->label('UOM')
                                                        ->options([
                                                            'pcs' => 'Pieces',
                                                            'kgs' => 'Kilograms',
                                                            'liters' => 'Liters',
                                                            'minutes' => 'Minutes',
                                                            'hours' => 'Hours',
                                                        ])
                                                        ->required(fn (callable $get) => $get('amount') !== null && $get('amount') !== '')
                                                        ->columnSpan(1),
                                                ])
                                                ->createItemButtonLabel('Add By Product')
                                                ->columnSpan('full'),
                                        ]),
                                ]),

                        Tabs\Tab::make('Quality Checking')
                            ->visible(fn (callable $get) => $get('operation_id'))
                            ->schema([
                                Section::make('Quality Check Labels')
                                    ->schema([
                                        // QC Passed Section
                                        Section::make('Select Labels QC Passed')
                                            ->collapsible()
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        Placeholder::make('selected_labels_count_qc_p')
                                                            ->label('Selected Labels Count')
                                                            ->content(function (callable $get) {
                                                                $labels = $get('selected_labels_qc_p');
                                                                return (is_array($labels) ? count($labels) : 0) . ' label(s) selected';
                                                            })
                                                            ->reactive(),

                                                        Checkbox::make('select_all_labels_qc_p')
                                                            ->label('Select All Labels')
                                                            ->default(false)
                                                            ->reactive()
                                                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                                                $labels = self::getAvailableLabels($get('model_id'));
                                                                $failedLabels = $get('selected_labels_qc_f') ?? [];
                                                                $availableLabels = array_diff(array_keys($labels), $failedLabels);

                                                                // Update the selected labels for QC Passed
                                                                $set('selected_labels_qc_p', $state ? $availableLabels : []);

                                                                // Reset range selection when "Select All" is used
                                                                if ($state) {
                                                                    $set('range_from_label_qc_p', null);
                                                                    $set('range_to_label_qc_p', null);
                                                                }
                                                            }),

                                                        Actions::make([
                                                            Action::make('clear_selection')
                                                                ->label('Clear All')
                                                                ->color('danger')
                                                                ->size('sm')
                                                                ->action(function (callable $set) {
                                                                    $set('selected_labels_qc_p', []);
                                                                    $set('select_all_labels_qc_p', false);
                                                                    $set('range_from_label_qc_p', null);
                                                                    $set('range_to_label_qc_p', null);
                                                                })
                                                        ])
                                                    ]),

                                                Fieldset::make('Label Selection')
                                                    ->schema([
                                                        Grid::make(12)
                                                            ->schema([
                                                                Actions::make([
                                                                    Action::make('select_first_10')
                                                                        ->label('First 10')
                                                                        ->size('sm')
                                                                        ->color('gray')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $labels = array_keys(self::getAvailableLabels($get('model_id')));
                                                                            $failedLabels = $get('selected_labels_qc_f') ?? [];
                                                                            $availableLabels = array_diff($labels, $failedLabels);

                                                                            $selected = array_slice($availableLabels, 0, 10);
                                                                            $currentSelected = $get('selected_labels_qc_p') ?: [];
                                                                            $set('selected_labels_qc_p', array_unique(array_merge($currentSelected, $selected)));
                                                                        }),
                                                                ])->columns(3),

                                                                Actions::make([
                                                                    Action::make('select_last_10')
                                                                        ->label('Last 10')
                                                                        ->size('sm')
                                                                        ->color('gray')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $labels = array_keys(self::getAvailableLabels($get('model_id')));
                                                                            $failedLabels = $get('selected_labels_qc_f') ?? [];
                                                                            $availableLabels = array_diff($labels, $failedLabels);

                                                                            $selected = array_slice($availableLabels, -10);
                                                                            $currentSelected = $get('selected_labels_qc_p') ?: [];
                                                                            $set('selected_labels_qc_p', array_unique(array_merge($currentSelected, $selected)));
                                                                        }),
                                                                ])->columns(3),

                                                                Actions::make([
                                                                    Action::make('select_even')
                                                                        ->label('Even Positions')
                                                                        ->size('sm')
                                                                        ->color('gray')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $labels = array_keys(self::getAvailableLabels($get('model_id')));
                                                                            $failedLabels = $get('selected_labels_qc_f') ?? [];
                                                                            $availableLabels = array_diff($labels, $failedLabels);

                                                                            $selected = [];
                                                                            foreach ($availableLabels as $index => $labelId) {
                                                                                if ($index % 2 === 0) {
                                                                                    $selected[] = $labelId;
                                                                                }
                                                                            }
                                                                            $currentSelected = $get('selected_labels_qc_p') ?: [];
                                                                            $set('selected_labels_qc_p', array_unique(array_merge($currentSelected, $selected)));
                                                                        }),
                                                                ])->columns(3),

                                                                Actions::make([
                                                                    Action::make('select_odd')
                                                                        ->label('Odd Positions')
                                                                        ->size('sm')
                                                                        ->color('gray')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $labels = array_keys(self::getAvailableLabels($get('model_id')));
                                                                            $failedLabels = $get('selected_labels_qc_f') ?? [];
                                                                            $availableLabels = array_diff($labels, $failedLabels);

                                                                            $selected = [];
                                                                            foreach ($availableLabels as $index => $labelId) {
                                                                                if ($index % 2 === 1) {
                                                                                    $selected[] = $labelId;
                                                                                }
                                                                            }
                                                                            $currentSelected = $get('selected_labels_qc_p') ?: [];
                                                                            $set('selected_labels_qc_p', array_unique(array_merge($currentSelected, $selected)));
                                                                        }),
                                                                ])->columns(3),
                                                            ]),

                                                        Grid::make(4)
                                                            ->schema([
                                                                TextInput::make('range_start_position')
                                                                    ->label('Start Position')
                                                                    ->numeric()
                                                                    ->minValue(1)
                                                                    ->placeholder('1'),

                                                                TextInput::make('range_end_position')
                                                                    ->label('End Position')
                                                                    ->numeric()
                                                                    ->minValue(fn (callable $get) => $get('range_start_position') ?: 1)
                                                                    ->placeholder('10'),

                                                                Actions::make([
                                                                    Action::make('select_numeric_range')
                                                                        ->label('Select by Position')
                                                                        ->color('info')
                                                                        ->size('sm')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $start = (int) $get('range_start_position') - 1;
                                                                            $end = (int) $get('range_end_position');
                                                                            $labels = array_keys(self::getAvailableLabels($get('model_id')));
                                                                            $failedLabels = $get('selected_labels_qc_f') ?? [];
                                                                            $availableLabels = array_diff($labels, $failedLabels);

                                                                            if ($start >= 0 && $end > $start && $start < count($availableLabels)) {
                                                                                $selected = array_slice($availableLabels, $start, $end - $start);
                                                                                $currentSelected = $get('selected_labels_qc_p') ?: [];
                                                                                $set('selected_labels_qc_p', array_unique(array_merge($currentSelected, $selected)));
                                                                            }
                                                                        })
                                                                ])
                                                            ]),
                                                    ]),

                                                // Label Picker
                                                Grid::make(1)
                                                    ->schema([
                                                        CheckboxList::make('selected_labels_qc_p')
                                                            ->label('Available Labels')
                                                            ->options(function (callable $get) {
                                                                $labels = self::getAvailableLabels($get('model_id'));
                                                                $failedLabels = $get('selected_labels_qc_f') ?? [];
                                                                return array_diff_key($labels, array_flip($failedLabels));
                                                            })
                                                            ->columns(4)
                                                            ->searchable()
                                                            ->live()
                                                            ->reactive()
                                                            ->required()
                                                            ->default([])
                                                            ->dehydrated()
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                $set('selected_labels_qc_p', is_array($state) ? $state : []);
                                                            })
                                                            ->bulkToggleable(false)
                                                    ])
                                            ]),

                                        // QC Failed Section
                                        Section::make('Select Labels QC Failed')
                                            ->collapsible()
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        Placeholder::make('selected_labels_count_qc_f')
                                                            ->label('Selected Labels Count')
                                                            ->content(function (callable $get) {
                                                                $labels = $get('selected_labels_qc_f');
                                                                return (is_array($labels) ? count($labels) : 0) . ' label(s) selected';
                                                            })
                                                            ->reactive(),

                                                        Checkbox::make('select_all_labels_qc_f')
                                                            ->label('Select All Labels')
                                                            ->default(false)
                                                            ->reactive()
                                                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                                                $labels = self::getAvailableLabels($get('model_id'));
                                                                $passedLabels = $get('selected_labels_qc_p') ?? [];
                                                                $availableLabels = array_diff(array_keys($labels), $passedLabels);

                                                                $set('selected_labels_qc_f', $state ? $availableLabels : []);

                                                                if ($state) {
                                                                    $set('range_from_label_qc_f', null);
                                                                    $set('range_to_label_qc_f', null);
                                                                }
                                                            }),

                                                        Actions::make([
                                                            Action::make('clear_selection')
                                                                ->label('Clear All')
                                                                ->color('danger')
                                                                ->size('sm')
                                                                ->action(function (callable $set) {
                                                                    $set('selected_labels_qc_f', []);
                                                                    $set('select_all_labels_qc_f', false);
                                                                    $set('range_from_label_qc_f', null);
                                                                    $set('range_to_label_qc_f', null);
                                                                })
                                                        ])
                                                    ]),

                                                Fieldset::make('Label Selection')
                                                    ->schema([
                                                        Grid::make(12)
                                                            ->schema([
                                                                Actions::make([
                                                                    Action::make('select_first_10')
                                                                        ->label('First 10')
                                                                        ->size('sm')
                                                                        ->color('gray')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $labels = array_keys(self::getAvailableLabels($get('model_id')));
                                                                            $passedLabels = $get('selected_labels_qc_p') ?? [];
                                                                            $availableLabels = array_diff($labels, $passedLabels);

                                                                            $selected = array_slice($availableLabels, 0, 10);
                                                                            $currentSelected = $get('selected_labels_qc_f') ?: [];
                                                                            $set('selected_labels_qc_f', array_unique(array_merge($currentSelected, $selected)));
                                                                        }),
                                                                ])->columns(3),

                                                                Actions::make([
                                                                    Action::make('select_last_10')
                                                                        ->label('Last 10')
                                                                        ->size('sm')
                                                                        ->color('gray')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $labels = array_keys(self::getAvailableLabels($get('model_id')));
                                                                            $passedLabels = $get('selected_labels_qc_p') ?? [];
                                                                            $availableLabels = array_diff($labels, $passedLabels);

                                                                            $selected = array_slice($availableLabels, -10);
                                                                            $currentSelected = $get('selected_labels_qc_f') ?: [];
                                                                            $set('selected_labels_qc_f', array_unique(array_merge($currentSelected, $selected)));
                                                                        }),
                                                                ])->columns(3),

                                                                Actions::make([
                                                                    Action::make('select_even')
                                                                        ->label('Even Positions')
                                                                        ->size('sm')
                                                                        ->color('gray')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $labels = array_keys(self::getAvailableLabels($get('model_id')));
                                                                            $passedLabels = $get('selected_labels_qc_p') ?? [];
                                                                            $availableLabels = array_diff($labels, $passedLabels);

                                                                            $selected = [];
                                                                            foreach ($availableLabels as $index => $labelId) {
                                                                                if ($index % 2 === 0) {
                                                                                    $selected[] = $labelId;
                                                                                }
                                                                            }
                                                                            $currentSelected = $get('selected_labels_qc_f') ?: [];
                                                                            $set('selected_labels_qc_f', array_unique(array_merge($currentSelected, $selected)));
                                                                        }),
                                                                ])->columns(3),

                                                                Actions::make([
                                                                    Action::make('select_odd')
                                                                        ->label('Odd Positions')
                                                                        ->size('sm')
                                                                        ->color('gray')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $labels = array_keys(self::getAvailableLabels($get('model_id')));
                                                                            $passedLabels = $get('selected_labels_qc_p') ?? [];
                                                                            $availableLabels = array_diff($labels, $passedLabels);

                                                                            $selected = [];
                                                                            foreach ($availableLabels as $index => $labelId) {
                                                                                if ($index % 2 === 1) {
                                                                                    $selected[] = $labelId;
                                                                                }
                                                                            }
                                                                            $currentSelected = $get('selected_labels_qc_f') ?: [];
                                                                            $set('selected_labels_qc_f', array_unique(array_merge($currentSelected, $selected)));
                                                                        }),
                                                                ])->columns(3),
                                                            ]),

                                                        Grid::make(4)
                                                            ->schema([
                                                                TextInput::make('range_start_position')
                                                                    ->label('Start Position')
                                                                    ->numeric()
                                                                    ->minValue(1)
                                                                    ->placeholder('1'),

                                                                TextInput::make('range_end_position')
                                                                    ->label('End Position')
                                                                    ->numeric()
                                                                    ->minValue(fn (callable $get) => $get('range_start_position') ?: 1)
                                                                    ->placeholder('10'),

                                                                Actions::make([
                                                                    Action::make('select_numeric_range')
                                                                        ->label('Select by Position')
                                                                        ->color('info')
                                                                        ->size('sm')
                                                                        ->action(function (callable $set, callable $get) {
                                                                            $start = (int) $get('range_start_position') - 1;
                                                                            $end = (int) $get('range_end_position');
                                                                            $labels = array_keys(self::getAvailableLabels($get('model_id')));
                                                                            $passedLabels = $get('selected_labels_qc_p') ?? [];
                                                                            $availableLabels = array_diff($labels, $passedLabels);

                                                                            if ($start >= 0 && $end > $start && $start < count($availableLabels)) {
                                                                                $selected = array_slice($availableLabels, $start, $end - $start);
                                                                                $currentSelected = $get('selected_labels_qc_f') ?: [];
                                                                                $set('selected_labels_qc_f', array_unique(array_merge($currentSelected, $selected)));
                                                                            }
                                                                        })
                                                                ])
                                                            ]),
                                                    ]),

                                                // Label Picker
                                                Grid::make(1)
                                                    ->schema([
                                                        CheckboxList::make('selected_labels_qc_f')
                                                            ->label('Available Labels')
                                                            ->options(function (callable $get) {
                                                                $labels = self::getAvailableLabels($get('model_id'));
                                                                $passedLabels = $get('selected_labels_qc_p') ?? [];
                                                                return array_diff_key($labels, array_flip($passedLabels));
                                                            })
                                                            ->columns(4)
                                                            ->searchable()
                                                            ->live()
                                                            ->reactive()
                                                            ->required()
                                                            ->default([])
                                                            ->dehydrated()
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                $set('selected_labels_qc_f', is_array($state) ? $state : []);
                                                            })
                                                            ->bulkToggleable(false)
                                                    ])
                                            ]),

                                        Section::make('Summary')
                                            ->schema([
                                                Placeholder::make('passed_items_count')
                                                    ->label('Number of Passed Items')
                                                    ->content(function (callable $get) {
                                                        $passedLabels = $get('selected_labels_qc_p') ?? [];
                                                        return count($passedLabels) . ' item(s)';
                                                    })
                                                    ->reactive(),

                                                Placeholder::make('failed_items_count')
                                                    ->label('Number of Failed Items')
                                                    ->content(function (callable $get) {
                                                        $failedLabels = $get('selected_labels_qc_f') ?? [];
                                                        return count($failedLabels) . ' item(s)';
                                                    })
                                                    ->reactive(),

                                                Fieldset::make('Action for Failed Items')
                                                    ->columns(2)
                                                    ->schema([
                                                    Select::make('failed_item_action')
                                                            ->label('Action for Failed Items')
                                                            ->options([
                                                                'cutting_section' => 'Cutting Section',
                                                                'sawing_section' => 'Sawing Section',
                                                            ])
                                                            ->visible(fn (callable $get) => count($get('selected_labels_qc_f') ?? []) >= 1)
                                                            ->required(fn (callable $get) => count($get('selected_labels_qc_f') ?? []) >= 1)
                                                            ->reactive() 
                                                            ->afterStateUpdated(function (callable $set) {
                                                                $set('cutting_station_id', null);
                                                                $set('sawing_operation_id', null);
                                                            }),

                                                        Select::make('cutting_station_id')
                                                            ->label('Cutting Station')
                                                            ->searchable()
                                                            ->options(function () {
                                                                return \App\Models\CuttingStation::all()
                                                                    ->pluck('name', 'id')
                                                                    ->toArray();
                                                            })
                                                            ->visible(fn (callable $get) => $get('failed_item_action') === 'cutting_section')
                                                            ->required(fn (callable $get) => $get('failed_item_action') === 'cutting_section') 
                                                            ->reactive(),

                                                    Select::make('sawing_operation_id')
                                                        ->label('Sawing Operation')
                                                        ->searchable() 
                                                        ->options(function () {
                                                            return \App\Models\AssignDailyOperationLine::where('status', 'completed')
                                                                ->with('assignDailyOperation') 
                                                                ->get()
                                                                ->mapWithKeys(function ($line) {
                                                                    $operation = $line->assignDailyOperation;
                                                                    if ($operation) {
                                                                        $label = sprintf(
                                                                            '%s - Order ID: %s - Operated Date: %s - Operated Line ID: %s',
                                                                            $operation->order_type ?? 'Unknown Type',
                                                                            $operation->order_id ?? 'Unknown ID',
                                                                            $operation->operation_date ?? 'Unknown Date',
                                                                            $line->id
                                                                        );
                                                                        return [$line->id => $label];
                                                                    }
                                                                    return [];
                                                                })
                                                                ->toArray();
                                                        })
                                                        ->placeholder('Search by Order ID') 
                                                        ->helperText('You can search by Order ID to find the operation.') 
                                                        ->visible(fn (callable $get) => $get('failed_item_action') === 'sawing_section')
                                                        ->required(fn (callable $get) => $get('failed_item_action') === 'sawing_section')
                                                        ->reactive(),
                                                        ]),
                                            ]),
                                    ]),
                        ]), 
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
                ...(
                Auth::user()->can('view audit columns')
                    ? [
                        TextColumn::make('created_by')->label('Created By')->toggleable()->sortable(),
                        TextColumn::make('updated_by')->label('Updated By')->toggleable()->sortable(),
                        TextColumn::make('created_at')->label('Created At')->toggleable()->dateTime()->sortable(),
                        TextColumn::make('updated_at')->label('Updated At')->toggleable()->dateTime()->sortable(),
                    ]
                    : []
                    ),
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