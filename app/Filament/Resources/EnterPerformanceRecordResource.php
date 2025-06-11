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
                $label->id => "ID - {$label->cuttingLabel->id} | Label -  {$label->cuttingLabel->label}"
            ])->toArray();
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

                                                    $employeeDetails = $employees->map(function ($employee) {
                                                        return [
                                                            'user_id' => $employee->user_id,
                                                            'name' => $employee->user->name ?? 'N/A',
                                                        ];
                                                    })->toArray();

                                                    $set('employee_details', $employeeDetails);
                                                    $set('employee_ids', $employees->pluck('user_id')->implode(', '));

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
    ->visible(fn (callable $get) => $get('operation_id'))
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

                        TextInput::make('emp_production')
                            ->label('Emp: Production')
                            ->numeric()
                            ->required()
                            ->reactive()
                            ->live()
                            ->columns(1),

                        TextInput::make('emp_downtime')
                            ->label('Emp: Downtime (min)')
                            ->reactive()
                            ->live()
                            ->columns(1),

                        TextInput::make('emp_waste')
                            ->label('Emp: Waste')
                            ->reactive()
                            ->live()
                            ->columns(1),

                        Section::make('Select Labels')
    ->collapsible()
    ->schema([
        // Range Selection
        Grid::make(3)
            ->schema([
                Select::make('range_start_label_id_e')
                    ->label('Start Label')
                    ->options(fn (callable $get) => self::getAvailableLabels($get('../../model_id')))
                    ->reactive()
                    ->searchable(),

                Select::make('range_end_label_id_e')
                    ->label('End Label')
                    ->options(fn (callable $get) => self::getAvailableLabels($get('../../model_id')))
                    ->reactive()
                    ->searchable(),

                Actions::make([
                    Action::make('apply_range_e')
                        ->label('Apply Label Range')
                        ->action(function (callable $get, callable $set) {
                            $labels = self::getAvailableLabels($get('../../model_id'));

                            $startId = $get('range_start_label_id_e');
                            $endId = $get('range_end_label_id_e');

                            if (!$startId || !$endId) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Invalid Range')
                                    ->body('Both Start Label and End Label must be selected.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $labelIds = array_keys($labels);
                            $startIndex = array_search($startId, $labelIds);
                            $endIndex = array_search($endId, $labelIds);

                            if ($startIndex === false || $endIndex === false) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Invalid Labels')
                                    ->body('Selected labels are not valid.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            if ($startIndex > $endIndex) {
                                [$startIndex, $endIndex] = [$endIndex, $startIndex];
                            }

                            $range = array_slice($labelIds, $startIndex, $endIndex - $startIndex + 1);
                            $existing = $get('selected_labels_e') ?? [];
                            $set('selected_labels_e', array_unique([...$existing, ...$range]));

                            \Filament\Notifications\Notification::make()
                                ->title('Labels Applied')
                                ->body(count($range) . ' labels have been applied.')
                                ->success()
                                ->send();
                        })
                        ->color('primary'),
                ]),
            ]),

        // Enter Label Field
        Grid::make(3)
    ->schema([
        TextInput::make('enter_label_e')
            ->label('Enter Label (Index or Barcode ID)')
            ->placeholder('Scan or enter index/barcode ID...')
            ->reactive()
            ->live()
            ->columns(2)
            ->extraAttributes([
                'onkeydown' => "if(event.key === 'Enter') { document.getElementById('select_label_e_button').click(); }"
            ]),

        Actions::make([
            Action::make('select_label_e')
                ->label('Enter')
                ->extraAttributes(['id' => 'select_label_e_button']) // Assign an ID for triggering via JavaScript
                ->action(function (callable $get, callable $set) {
                    $labels = self::getAvailableLabels($get('../../model_id'));
                    $enteredLabel = $get('enter_label_e');

                    if (!$enteredLabel) {
                        \Filament\Notifications\Notification::make()
                            ->title('Invalid Input')
                            ->body('Please scan or enter a valid index or barcode ID.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Match entered label with available labels
                    $selectedLabel = collect($labels)->filter(function ($label, $key) use ($enteredLabel) {
                        return $key == $enteredLabel || str_contains($label, $enteredLabel);
                    })->keys()->first();

                    if (!$selectedLabel) {
                        \Filament\Notifications\Notification::make()
                            ->title('Label Not Found')
                            ->body('No label matches the scanned or entered index/barcode ID.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Add selected label to the list
                    $existing = $get('selected_labels_e') ?? [];
                    $set('selected_labels_e', array_unique([...$existing, $selectedLabel]));

                    \Filament\Notifications\Notification::make()
                        ->title('Label Selected')
                        ->body('Label has been successfully selected.')
                        ->success()
                        ->send();
                })
                ->color('primary'),
        ]),
    ]),

        // Select All + Count
        Grid::make(2)
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
                    }),
            ]),

        // Label Picker
        Grid::make(1)
            ->schema([
                CheckboxList::make('selected_labels_e')
                    ->label('Available Labels')
                    ->default([])
                    ->options(fn (callable $get) => self::getAvailableLabels($get('../../model_id')))
                    ->columns(3)
                    ->reactive()
                    ->searchable()
                    ->live()
                    ->dehydrated(),
            ]),
        ]),
                    ]),
            ]),
        ]),
                        
                        Tabs\Tab::make('Machines')
                            ->visible(fn (callable $get) => $get('operation_id'))
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

                                                Section::make('Select Machine Labels')
                                                    ->collapsible()
                                                    ->schema([
                                                        // Range Selection
                                                        Grid::make(3)
                                                            ->schema([
                                                                Select::make('range_start_label_id_m')
                                                                    ->label('Start Label')
                                                                    ->options(fn (callable $get) => self::getAvailableLabels(
                                                                        $get('../../operation_type'),
                                                                        $get('../../model_id'),
                                                                        $get('../../order_type'),
                                                                        $get('../../order_id'),
                                                                    ))
                                                                    ->reactive()
                                                                    ->searchable(),

                                                                Select::make('range_end_label_id_m')
                                                                    ->label('End Label')
                                                                    ->options(fn (callable $get) => self::getAvailableLabels(
                                                                        $get('../../operation_type'),
                                                                        $get('../../model_id'),
                                                                        $get('../../order_type'),
                                                                        $get('../../order_id'),
                                                                    ))
                                                                    ->reactive()
                                                                    ->searchable(),

                                                                Actions::make([
                                                                    Action::make('apply_range_m')
                                                                        ->label('Apply Label Range')
                                                                        ->action(function ($get, $set) {
                                                                            $labels = self::getAvailableLabels(
                                                                                $get('operation_type'),
                                                                                $get('model_id'),
                                                                                $get('order_type'),
                                                                                $get('order_id')
                                                                            );

                                                                            $startId = $get('range_start_label_id_m');
                                                                            $endId = $get('range_end_label_id_m');
                                                                            if (!$startId || !$endId) return;

                                                                            $labelIds = array_keys($labels);
                                                                            $startIndex = array_search($startId, $labelIds);
                                                                            $endIndex = array_search($endId, $labelIds);

                                                                            if ($startIndex === false || $endIndex === false) return;
                                                                            if ($startIndex > $endIndex) [$startIndex, $endIndex] = [$endIndex, $startIndex];

                                                                            $range = array_slice($labelIds, $startIndex, $endIndex - $startIndex + 1);
                                                                            $existing = $get('selected_labels') ?? [];
                                                                            $set('selected_labels_m', array_unique([...$existing, ...$range]));
                                                                        })
                                                                        ->color('primary'),
                                                                ]),
                                                            ]),

                                                        // Select All + Count
                                                        Grid::make(2)
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
                                                                        $labels = self::getAvailableLabels(
                                                                            $get('operation_type'),
                                                                            $get('model_id'),
                                                                            $get('order_type'),
                                                                            $get('order_id')
                                                                        );

                                                                        $set('selected_labels_m', $state ? array_keys($labels) : []);
                                                                    }),
                                                            ]),

                                                        // Label Picker
                                                        Grid::make(1)
                                                            ->schema([
                                                                CheckboxList::make('selected_labels_m')
                                                                    ->label('Available Labels')
                                                                    ->default([]) 
                                                                    ->options(fn (callable $get) => self::getAvailableLabels(
                                                                        $get('../../operation_type'),
                                                                        $get('../../model_id'),
                                                                        $get('../../order_type'),
                                                                        $get('../../order_id')
                                                                    ))
                                                                    ->columns(3)
                                                                    ->reactive()
                                                                    ->searchable()
                                                                    ->live()
                                                                    ->dehydrated(),
                                                            ]),
                                                        ]),
                                                
                                                TextInput::make('machine_output')->label('Machine Output')->numeric()->required()->reactive()->live()->columns(1),
                                                TextInput::make('machine_waste')->label('Machine Waste')->numeric()->required()->reactive()->live()->columns(1),
                                                TextInput::make('machine_downtime')->label('Downtime (min)')->numeric()->reactive()->live()->columns(1),
                                                TextArea::make('machine_notes')->label('Notes (Machines)')->columns(4),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Summary')
                                    ->schema([
                                        Placeholder::make('machine_total_output')
                                            ->label('Machine: Total Output')
                                            ->content(function (callable $get, callable $set) {
                                                $details = $get('machines') ?? [];
                                                $total = collect($details)->sum('machine_output') ?: 0;
                                                $set('machine_total_output', $total); 
                                                return $total;
                                            })
                                            ->reactive()
                                            ->live(),

                                        Placeholder::make('machine_total_waste')
                                            ->label('Machine: Total Waste')
                                            ->content(function (callable $get, callable $set) {
                                                $details = $get('machines') ?? [];
                                                $total = collect($details)->sum('machine_waste') ?: 0;
                                                $set('machine_total_waste', $total); 
                                                return $total;
                                            })
                                            ->reactive()
                                            ->live(),
                                            
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

                                        Hidden::make('machine_total_output')
                                            ->dehydrated(),
                                        Hidden::make('machine_total_waste')
                                            ->dehydrated(),
                                        Hidden::make('machine_total_downtime')
                                            ->dehydrated(),
                                    ])
                            ]),

                        Tabs\Tab::make('Supervisors')
                            ->visible(fn (callable $get) => $get('operation_id'))
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
                            ->visible(fn (callable $get) => $get('operation_id'))
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

                                //  Section 1: Summary
                                Section::make('Production Summary')
                                    ->columns(3)
                                    ->schema([
                                        Placeholder::make('live_emp_total_production')
                                            ->label('Emp: Total Production')
                                            ->content(fn (callable $get) => $get('emp_total_production') ?: 0)
                                            ->reactive(),

                                        Placeholder::make('live_emp_total_waste')
                                            ->label('Emp: Total Waste')
                                            ->content(fn (callable $get) => $get('emp_total_waste') ?: 0)
                                            ->reactive(),

                                        Placeholder::make('live_emp_total_downtime')
                                            ->label('Emp: Total Downtime (min)')
                                            ->content(fn (callable $get) => $get('emp_total_downtime') ?: 0)
                                            ->reactive(),

                                        Placeholder::make('live_machine_total_output')
                                            ->label('Machine: Total Production')
                                            ->content(fn (callable $get) => $get('machine_total_output') ?: 0)
                                            ->reactive(),

                                        Placeholder::make('live_machine_total_waste')
                                            ->label('Machine: Total Waste')
                                            ->content(fn (callable $get) => $get('machine_total_waste') ?: 0)
                                            ->reactive(),

                                        Placeholder::make('live_machine_total_downtime')
                                            ->label('Machine: Total Downtime (min)')
                                            ->content(fn (callable $get) => $get('machine_total_downtime') ?: 0)
                                            ->reactive(),

                                        Placeholder::make('live_total_sup_quantity')
                                            ->label('Total Supervisored Quantity')
                                            ->content(fn (callable $get) => $get('total_sup_quantity') ?: 0)
                                            ->reactive(),

                                        Placeholder::make('live_total_acc_quantity')
                                            ->label('Total Accepted Quantity')
                                            ->content(fn (callable $get) => $get('total_acc_quantity') ?: 0)
                                            ->reactive(),

                                        Placeholder::make('live_total_rej_quantity')
                                            ->label('Total Rejected Quantity')
                                            ->content(fn (callable $get) => $get('total_rej_quantity') ?: 0)
                                            ->reactive(),

                                        Placeholder::make('live_process_total_cost')
                                            ->label('Total Third-Party Process Cost')
                                            ->content(fn (callable $get) => $get('process_total_cost') ?: 0)
                                            ->reactive(),
                                    ]),

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
                                    Section::make('Quality Check')
                                        ->schema([
                                            Placeholder::make('quality_check_placeholder')
                                                ->label('Quality check form will be defined later')
                                                ->content('Coming soon...')
                                        ])
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