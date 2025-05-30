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
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Forms\Components\Tab;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Set;


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
                                            }),
                                        ]),

                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        Select::make('operation_type')
                                            ->label('Operation Type')
                                            ->options([
                                                'assigned' => 'Assigned Daily Operation',
                                                'um' => 'UM Operation',
                                                'temp' => 'Temporary Operation',
                                            ])
                                            ->required()
                                            ->reactive()
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
                                            }),

                                        Select::make('operation_id')
                                            ->label('Operation')
                                            ->reactive()
                                            ->columns(1)
                                            ->required()
                                            ->searchable()
                                            ->options(function (callable $get) {
                                                $operationType = $get('operation_type');
                                                $operatedDate = $get('operated_date');

                                                if (!$operationType || !$operatedDate) return [];

                                                return match ($operationType) {
                                                    'assigned' => \App\Models\AssignDailyOperationLine::with(['operation', 'workstation', 'productionLine'])
                                                        ->whereHas('assignDailyOperation', fn($q) => $q->whereDate('operation_date', $operatedDate))
                                                        ->get()
                                                        ->mapWithKeys(fn($line) => [
                                                            $line->id => "Assigned Line - {$line->id} | {$line->assignDailyOperation->order_type} - {$line->assignDailyOperation->order_id} ",
                                                        ]),

                                                    'um' => \App\Models\UMOperationLine::with(['operation', 'workstation', 'productionLine'])
                                                        ->whereHas('umOperation', fn($q) => $q->whereDate('operation_date', $operatedDate))
                                                        ->get()
                                                        ->mapWithKeys(fn($line) => [
                                                            $line->id => "Setted Line - {$line->id} | {$line->umOperation->order_type} - {$line->umOperation->order_id}" ,
                                                        ]),

                                                    'temp' => \App\Models\TemporaryOperation::with(['workstation', 'productionLine'])
                                                        ->whereDate('created_at', $operatedDate)
                                                        ->get()
                                                        ->mapWithKeys(fn($op) => [
                                                            $op->id => "Temporary OP Line - {$op->id} | {$op->order_type} - {$op->order_id} ",
                                                        ]),

                                                    default => [],
                                                };
                                            })
                                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                $operationType = $get('operation_type');
                                                if (!$operationType || !$state) return;

                                                $model = match ($operationType) {
                                                    'assigned' => \App\Models\AssignDailyOperationLine::with(['operation', 'productionLine', 'workstation'])->find($state),
                                                    'um' => \App\Models\UMOperationLine::with(['operation', 'productionLine', 'workstation'])->find($state),
                                                    'temp' => \App\Models\TemporaryOperation::with(['productionLine', 'workstation'])->find($state),
                                                    default => null,
                                                };

                                                if ($model) {
                                                    $set('operation_type', $operationType);
                                                    $set('order_type', $model->assignDailyOperation->order_type ?? $model->umOperation->order_type ?? $model->order_type ?? null);
                                                    $set('order_id', $model->assignDailyOperation->order_id ?? $model->umOperation->order_id ?? $model->order_id ?? null);
                                                    $set('operation_date', $model->assignDailyOperation->operation_date ?? $model->umOperation->operation_date ?? $model->operation_date?? null);
                                                    $set('machine_setup_time', $model->machine_setup_time ?? 0);
                                                    $set('machine_run_time', $model->machine_run_time ?? 0);
                                                    $set('labor_setup_time', $model->labor_setup_time ?? 0);
                                                    $set('labor_run_time', $model->labor_run_time ?? 0);
                                                    $set('target_duration', $model->target_duration ?? null);
                                                    $set('target', $model->target ?? null);
                                                    $set('measurement_unit', $model->measurement_unit ?? null);
                                                }
                                            }),

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
                                    ]),
                            ]),

                        Tabs\Tab::make('Production Data')
                            ->schema([
                                \Filament\Forms\Components\Section::make('Pre-Defined Performance Values')
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


                                \Filament\Forms\Components\Section::make('Actual Performance Values')
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
                                        Repeater::make('time_frames')
                                            ->label('Operated Time Frames & Production')
                                            ->columnSpanFull()
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

                                                        TextInput::make('actual_production')
                                                            ->label('Actual Production')
                                                            ->numeric()
                                                            ->required()
                                                            ->reactive()
                                                            ->columnSpan(2),

                                                        TextInput::make('measurement_unit')
                                                            ->label('Measurement Unit')
                                                            ->disabled()
                                                            ->columnSpan(1),
                                                        
                                                       TextInput::make('waste')
                                                            ->label('Waste')
                                                            ->numeric()
                                                            ->reactive()
                                                            ->columnSpan(2),

                                                        Select::make('waste_measurement_unit')
                                                            ->label('Waste Measurement Unit')
                                                            ->options([
                                                                'pcs' => 'Pieces',
                                                                'kgs' => 'Kilograms',
                                                                'liters' => 'Liters',
                                                                'minutes' => 'Minutes',
                                                                'hours' => 'Hours',
                                                            ])
                                                            ->required(fn (callable $get) => $get('waste') !== null && $get('waste') !== '')
                                                            ->visible(fn (callable $get) => $get('waste') !== null && $get('waste') !== '')
                                                            ->columnSpan(1),

                                                        Select::make('waste_item_id')
                                                            ->label('Waste Item')
                                                            ->searchable()
                                                            ->options(function () {
                                                                return InventoryItem::query()
                                                                    ->orderBy('item_code')
                                                                    ->get()
                                                                    ->mapWithKeys(fn ($item) => [$item->id => "{$item->item_code} - {$item->name}"])
                                                                    ->toArray();
                                                            })
                                                            ->required(fn (callable $get) => $get('waste') !== null && $get('waste') !== '')
                                                            ->visible(fn (callable $get) => $get('waste') !== null && $get('waste') !== '')
                                                            ->columnSpan(3),
                                                    ]),
                                            ])
                                            ->columns(1)
                                            ->reorderable()
                                            ->defaultItems(1)
                                            ->minItems(1)
                                            ->addActionLabel('Add Time Frame'),

                                        ]),
                            ]),

                        Tabs\Tab::make('Employees')
                            ->visible(fn (callable $get) => $get('operation_id'))
                            ->schema([
                                Section::make()
                                    ->columns(1)
                                    ->schema([
                                        Placeholder::make('employees_table')
                                            ->content(function (callable $get) {
                                                $operationId = $get('operation_id');
                                                $operationType = $get('operation_type');
                                                
                                                if (!$operationId || !$operationType) {
                                                    return 'No operation selected.';
                                                }
                                                
                                                $employees = match ($operationType) {
                                                    'assigned' => \App\Models\AssignedEmployee::with('user')
                                                        ->where('assign_daily_operation_line_id', $operationId)
                                                        ->get(),
                                                    'um' => \App\Models\UMOperationLineEmployee::with('user')
                                                        ->where('u_m_operation_line_id', $operationId)
                                                        ->get(),
                                                    'temp' => \App\Models\TemporaryOperationEmployee::with('user')
                                                        ->where('temporary_operation_id', $operationId)
                                                        ->get(),
                                                    default => collect(),
                                                };
                                                
                                                if ($employees->isEmpty()) {
                                                    return 'No employees assigned.';
                                                }
                                                
                                                $html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">';
                                                $html .= '<thead><tr>';
                                                $html .= '<th class="px-4 py-2">Employee ID</th>';
                                                $html .= '<th class="px-4 py-2">Name</th>';
                                                $html .= '<th class="px-4 py-2">Role</th>';
                                                $html .= '</tr></thead><tbody>';
                                                
                                                foreach ($employees as $employee) {
                                                    $html .= '<tr>';
                                                    $html .= '<td class="px-4 py-2">'.$employee->user_id.'</td>';
                                                    $html .= '<td class="px-4 py-2">'.($employee->user->name ?? 'N/A').'</td>';
                                                    $html .= '<td class="px-4 py-2">'.($employee->user->role ?? 'N/A').'</td>';
                                                    $html .= '</tr>';
                                                }
                                                
                                                $html .= '</tbody></table></div>';
                                                
                                                return new HtmlString($html);
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Machines')
                            ->visible(fn (callable $get) => $get('operation_id'))
                            ->schema([
                                Section::make()
                                    ->columns(1)
                                    ->schema([
                                        Placeholder::make('machines_table')
                                            ->content(function (callable $get) {
                                                $operationId = $get('operation_id');
                                                $operationType = $get('operation_type');
                                                
                                                if (!$operationId || !$operationType) {
                                                    return 'No operation selected.';
                                                }
                                                
                                                $machines = match ($operationType) {
                                                    'assigned' => \App\Models\AssignedProductionMachine::with('productionMachine')
                                                        ->where('assign_daily_operation_line_id', $operationId)
                                                        ->get(),
                                                    'um' => \App\Models\UMOperationLineMachine::with('productionMachine')
                                                        ->where('u_m_operation_line_id', $operationId)
                                                        ->get(),
                                                    'temp' => \App\Models\TemporaryOperationProductionMachine::with('productionMachine')
                                                        ->where('temporary_operation_id', $operationId)
                                                        ->get(),
                                                    default => collect(),
                                                };
                                                
                                                if ($machines->isEmpty()) {
                                                    return 'No machines assigned.';
                                                }
                                                
                                                $html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">';
                                                $html .= '<thead><tr>';
                                                $html .= '<th class="px-4 py-2">Machine ID</th>';
                                                $html .= '<th class="px-4 py-2">Name</th>';
                                                $html .= '<th class="px-4 py-2">Type</th>';
                                                $html .= '</tr></thead><tbody>';
                                                
                                                foreach ($machines as $machine) {
                                                    $html .= '<tr>';
                                                    $html .= '<td class="px-4 py-2">'.$machine->production_machine_id.'</td>';
                                                    $html .= '<td class="px-4 py-2">'.($machine->productionMachine->name ?? 'N/A').'</td>';
                                                    $html .= '<td class="px-4 py-2">'.($machine->productionMachine->type ?? 'N/A').'</td>';
                                                    $html .= '</tr>';
                                                }
                                                
                                                $html .= '</tbody></table></div>';
                                                
                                                return new HtmlString($html);
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Supervisors')
                            ->visible(fn (callable $get) => $get('operation_id'))
                            ->schema([
                                Section::make()
                                    ->columns(1)
                                    ->schema([
                                        Placeholder::make('supervisors_table')
                                            ->content(function (callable $get) {
                                                $operationId = $get('operation_id');
                                                $operationType = $get('operation_type');
                                                
                                                if (!$operationId || !$operationType) {
                                                    return 'No operation selected.';
                                                }
                                                
                                                $supervisors = match ($operationType) {
                                                    'assigned' => \App\Models\AssignedSupervisor::with('user')
                                                        ->where('assign_daily_operation_line_id', $operationId)
                                                        ->get(),
                                                    'um' => \App\Models\UMOperationLineSupervisor::with('user')
                                                        ->where('u_m_operation_line_id', $operationId)
                                                        ->get(),
                                                    'temp' => \App\Models\TemporaryOperationSupervisor::with('user')
                                                        ->where('temporary_operation_id', $operationId)
                                                        ->get(),
                                                    default => collect(),
                                                };
                                                
                                                if ($supervisors->isEmpty()) {
                                                    return 'No supervisors assigned.';
                                                }
                                                
                                                $html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">';
                                                $html .= '<thead><tr>';
                                                $html .= '<th class="px-4 py-2">Supervisor ID</th>';
                                                $html .= '<th class="px-4 py-2">Name</th>';
                                                $html .= '<th class="px-4 py-2">Role</th>';
                                                $html .= '</tr></thead><tbody>';
                                                
                                                foreach ($supervisors as $supervisor) {
                                                    $html .= '<tr>';
                                                    $html .= '<td class="px-4 py-2">'.$supervisor->user_id.'</td>';
                                                    $html .= '<td class="px-4 py-2">'.($supervisor->user->name ?? 'N/A').'</td>';
                                                    $html .= '<td class="px-4 py-2">'.($supervisor->user->role ?? 'N/A').'</td>';
                                                    $html .= '</tr>';
                                                }
                                                
                                                $html .= '</tbody></table></div>';
                                                
                                                return new HtmlString($html);
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Services')
                            ->visible(fn (callable $get) => $get('operation_id'))
                            ->schema([
                                Section::make()
                                    ->columns(1)
                                    ->schema([
                                        Placeholder::make('services_table')
                                            ->content(function (callable $get) {
                                                $operationId = $get('operation_id');
                                                $operationType = $get('operation_type');
                                                
                                                if (!$operationId || !$operationType) {
                                                    return 'No operation selected.';
                                                }
                                                
                                                $services = match ($operationType) {
                                                    'assigned' => \App\Models\AssignedThirdPartyService::with('thirdPartyService')
                                                        ->where('assign_daily_operation_line_id', $operationId)
                                                        ->get(),
                                                    'um' => \App\Models\UMOperationLineService::with('thirdPartyService')
                                                        ->where('u_m_operation_line_id', $operationId)
                                                        ->get(),
                                                    'temp' => \App\Models\TemporaryOperationService::with('thirdPartyService')
                                                        ->where('temporary_operation_id', $operationId)
                                                        ->get(),
                                                    default => collect(),
                                                };
                                                
                                                if ($services->isEmpty()) {
                                                    return 'No services assigned.';
                                                }
                                                
                                                $html = '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">';
                                                $html .= '<thead><tr>';
                                                $html .= '<th class="px-4 py-2">Service ID</th>';
                                                $html .= '<th class="px-4 py-2">Name</th>';
                                                $html .= '<th class="px-4 py-2">Type</th>';
                                                $html .= '</tr></thead><tbody>';
                                                
                                                foreach ($services as $service) {
                                                    $html .= '<tr>';
                                                    $html .= '<td class="px-4 py-2">'.$service->third_party_service_id.'</td>';
                                                    $html .= '<td class="px-4 py-2">'.($service->thirdPartyService->name ?? 'N/A').'</td>';
                                                    $html .= '<td class="px-4 py-2">'.($service->thirdPartyService->type ?? 'N/A').'</td>';
                                                    $html .= '</tr>';
                                                }
                                                
                                                $html .= '</tbody></table></div>';
                                                
                                                return new HtmlString($html);
                                            })
                                            ->columnSpanFull(),
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