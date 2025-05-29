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
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\{Select, Textarea, Grid, Section, Repeater, TextInput};
use Filament\Forms\Components\ButtonAction;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions\Button;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;


class AssignDailyOperationsResource extends Resource
{
    protected static ?string $model = AssignDailyOperation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Daily Production';
    protected static ?string $navigationLabel = 'Assign Daily Operations - Orders with Released Materials';
    protected static ?string $label = 'Assign Daily Operations(Released Materials)';

    public static function form(Form $form): Form
    {
        return $form->schema([
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
                                    $set('workstation_name', null);
                                    $set('production_line_name', null);
                                    $set('released_workstation_id', null);
                                    $set('workstation_id', null);
                                    $set('production_line_id', null);
                                    $set('available_workstations', []);
                                    $set('daily_operations', []);
                                }),

                            Select::make('order_id')
                                ->label('Order')
                                ->required()
                                ->disabled(fn ($get, $record) => $record !== null)
                                ->dehydrated()
                                ->options(function ($get) {
                                    $orderType = $get('order_type');
                                    if ($orderType === 'customer_order') {
                                        return \App\Models\CustomerOrder::pluck('name', 'order_id');
                                    } elseif ($orderType === 'sample_order') {
                                        return \App\Models\SampleOrder::pluck('name', 'order_id');
                                    }
                                    return [];
                                })
                                ->reactive()
                                ->hidden(fn ($get) => !$get('order_type')) 
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    // Clear dependent fields when order_id changes
                                    $set('customer_id', null);
                                    $set('wanted_date', null);
                                    $set('workstation_name', null);
                                    $set('production_line_name', null);
                                    $set('released_workstation_id', null);
                                    $set('workstation_id', null);
                                    $set('production_line_id', null);
                                    $set('available_workstations', []);
                                    $set('daily_operations', []);

                                    $orderType = $get('order_type');
                                    if ($orderType && $state) {
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

                                        // Fetch the related ReleaseMaterial record
                                        $releaseMaterial = \App\Models\ReleaseMaterial::where('order_type', $orderType)
                                            ->where('order_id', $state)
                                            ->first();

                                        $set('workstation_name', $releaseMaterial->workstation->name ?? 'N/A');
                                        $set('production_line_name', $releaseMaterial->productionLine->name ?? 'N/A');
                                        $set('released_workstation_id', $releaseMaterial->workstation_id ?? 'N/A');
                                        $set('workstation_id', $releaseMaterial->workstation_id ?? 'N/A');
                                        $set('production_line_id', $releaseMaterial->production_line_id ?? 'N/A');
                                    }

                                    // Automatically load available workstations
                                    $productionLineId = $get('production_line_id');
                                    if ($productionLineId) {
                                        $workstations = \App\Models\Workstation::where('production_line_id', $productionLineId)->get();
                                        $set('available_workstations', $workstations->map(fn($ws) => [
                                            'id' => $ws->id,
                                            'name' => $ws->name,
                                            'description' => $ws->description,
                                            'status' => $ws->status
                                        ])->toArray());
                                    } else {
                                        $set('available_workstations', []);
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

                                        $releaseMaterial = \App\Models\ReleaseMaterial::where('order_type', $orderType)
                                            ->where('order_id', $state)
                                            ->first();

                                        $set('workstation_name', $releaseMaterial->workstation->name ?? 'N/A');
                                        $set('production_line_name', $releaseMaterial->productionLine->name ?? 'N/A');
                                        $set('released_workstation_id', $releaseMaterial->workstation_id ?? 'N/A');
                                        $set('workstation_id', $releaseMaterial->workstation_id ?? 'N/A');
                                        $set('production_line_id', $releaseMaterial->production_line_id ?? 'N/A');

                                        $productionLineId = $releaseMaterial->production_line_id ?? null;
                                        if ($productionLineId) {
                                            $workstations = \App\Models\Workstation::where('production_line_id', $productionLineId)->get();
                                            $set('available_workstations', $workstations->map(fn ($ws) => [
                                                'id' => $ws->id,
                                                'name' => $ws->name,
                                                'description' => $ws->description,
                                                'status' => $ws->status,
                                            ])->toArray());
                                        } else {
                                            $set('available_workstations', []);
                                        }
                                    }
                                }),
                                
                            TextInput::make('workstation_name')
                                ->label('Workstation Name - Released Items')
                                ->disabled(),

                            TextInput::make('production_line_name')
                                ->label('Production Line Name')
                                ->disabled(),

                            TextInput::make('workstation_id')->label('Workstation ID')->disabled()->hidden(),
                            TextInput::make('production_line_id')->label('Production Line ID')->disabled()->hidden(),

                            TextInput::make('customer_id')
                                ->label('Customer ID')
                                ->disabled(),

                            TextInput::make('wanted_date')
                                ->label('Wanted Date')
                                ->disabled(),
                        ]),
                ]),
            
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


            Section::make('Select From Pre-Defined Operations')
                ->schema([
                    Grid::make(2)
                        ->schema([                            
                            Select::make('selected_workstation_id')
                                ->label('Select Workstation')
                                ->options(function ($get) {
                                    $availableWorkstations = $get('available_workstations') ?? [];
                                    return collect($availableWorkstations)->pluck('name', 'id')->toArray();
                                })
                                ->reactive()
                                ->required(fn ($livewire) => get_class($livewire) === \App\Filament\Resources\AssignDailyOperationsResource\Pages\CreateAssignDailyOperations::class)
                                ->hidden(fn ($get) => !$get('order_id')),

                            Select::make('selected_operation_id')
                                ->label('Select Operation')
                                ->options(function ($get) {
                                    $workstationId = $get('selected_workstation_id');
                                    if ($workstationId) {
                                        return \App\Models\Operation::where('workstation_id', $workstationId)
                                            ->pluck('description', 'id')
                                            ->toArray();
                                    }
                                    return \App\Models\Operation::pluck('description', 'id')->toArray();
                                })
                                ->reactive()
                                ->required()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $operation = \App\Models\Operation::find($state);
                                    if ($operation) {
                                        $set('selected_operation_description', $operation->description);
                                    }
                                })
                                ->hidden(fn ($get) => !$get('selected_workstation_id')),
                        ]),
                        

                    Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('add_operation')
                        ->label('Add Operation')
                        ->button()
                        ->action(function ($get, $set) {
                            $selectedWorkstationId = $get('selected_workstation_id');
                            $selectedOperationId = $get('selected_operation_id');
                            $productionLineId = $get('production_line_id'); // Get production_line_id from form
                            
                            if (!$selectedWorkstationId || !$selectedOperationId || !$productionLineId) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Workstation, Operation, and Production Line must be selected')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            $operation = \App\Models\Operation::find($selectedOperationId);
                            $workstation = \App\Models\Workstation::find($selectedWorkstationId);
                            
                            if (!$operation || $operation->status === 'inactive') {
                                Notification::make()
                                    ->title('Error')
                                    ->body('The selected operation is inactive and cannot be added.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            
                            $currentOperations = $get('daily_operations') ?? [];
                            
                            if (collect($currentOperations)->contains('operation_id', $selectedOperationId)) {
                                return;
                            }
                            
                            $sequence = count($currentOperations) + 1;

                            // Prepare default values from the Operation model
                            $employeeIds = $operation->employee_id ? (is_array($operation->employee_id) ? $operation->employee_id : [$operation->employee_id]) : [];
                            $supervisorIds = $operation->supervisor_id ? (is_array($operation->supervisor_id) ? $operation->supervisor_id : [$operation->supervisor_id]) : [];
                            $machineIds = $operation->machine_id ? (is_array($operation->machine_id) ? $operation->machine_id : [$operation->machine_id]) : [];
                            $thirdPartyServiceIds = $operation->third_party_service_id ? (is_array($operation->third_party_service_id) ? $operation->third_party_service_id : [$operation->third_party_service_id]) : [];

                            $newOperation = [
                                'production_line_id' => $productionLineId,
                                'workstation_id' => $selectedWorkstationId,
                                'workstation_name' => $workstation->name,
                                'operation_id' => $selectedOperationId,
                                'operation_description' => $operation->description,
                                'sequence' => $sequence,
                                'status' => $operation->status,
                                'machine_setup_time' => $operation->machine_setup_time,
                                'machine_run_time' => $operation->machine_run_time,
                                'labor_setup_time' => $operation->labor_setup_time,
                                'labor_run_time' => $operation->labor_run_time,
                                'employee_ids' => $employeeIds,
                                'supervisor_ids' => $supervisorIds,
                                'machine_ids' => $machineIds,
                                'third_party_service_ids' => $thirdPartyServiceIds,
                            ];
                            
                            $currentOperations[] = $newOperation;
                            $set('daily_operations', $currentOperations);
                        }),         
                ])->columnSpanFull(),
            ]),

            Section::make('Daily Operation Lines')
                ->schema([
                    Grid::make()->columns(1)
                        ->schema([
                            Repeater::make('daily_operations')
                                ->label('Operations Sequence')
                                ->schema([
                                    TextInput::make('production_line_id')
                                        ->label('Production Line ID')
                                        ->disabled()
                                        ->dehydrated()
                                        ->reactive(),
                                        
                                    TextInput::make('workstation_id')
                                        ->label('Workstation ID')
                                        ->disabled()
                                        ->hidden(),

                                    TextInput::make('operation_id')
                                        ->label('Operation ID')
                                        ->disabled()
                                        ->hidden(),
                                        
                                    TextInput::make('workstation_name')
                                        ->label('Workstation Name')
                                        ->disabled(),
                                                            
                                    TextInput::make('operation_description')
                                        ->label('Operation Description')
                                        ->required(),

                                    // Add MultiSelects for multiple assignments
                                    Forms\Components\MultiSelect::make('employee_ids')
                                        ->label('Employees')
                                        ->options(
                                            \App\Models\User::role('employee')->pluck('name', 'id')
                                        )
                                        ->searchable()
                                        ->required(),

                                    Forms\Components\MultiSelect::make('supervisor_ids')
                                        ->label('Supervisors')
                                        ->options(
                                            \App\Models\User::role('supervisor')->pluck('name', 'id')
                                        )
                                        ->searchable(),

                                    Forms\Components\MultiSelect::make('machine_ids')
                                        ->label('Machines')
                                        ->options(\App\Models\ProductionMachine::pluck('name', 'id'))
                                        ->searchable(),

                                    Forms\Components\MultiSelect::make('third_party_service_ids')
                                        ->label('Third Party Services')
                                        ->options(\App\Models\ThirdPartyService::pluck('name', 'id'))
                                        ->searchable(),
                                    
                                    TextInput::make('machine_setup_time')
                                        ->label('Machine Setup Time')
                                        ->numeric()
                                        ->default(0)
                                        ->dehydrated()
                                        ->reactive(),
                                        
                                    TextInput::make('labor_setup_time')
                                        ->label('Labor Setup Time')
                                        ->numeric()
                                        ->default(0)
                                        ->dehydrated()
                                        ->reactive(),

                                    TextInput::make('machine_run_time')
                                        ->label('Machine Run Time')
                                        ->numeric()
                                        ->default(0)
                                        ->dehydrated()
                                        ->reactive(),
                                        
                                    TextInput::make('labor_run_time')
                                        ->label('Labor Run Time')
                                        ->numeric()
                                        ->default(0)
                                        ->dehydrated()
                                        ->reactive(),
                                        
                                    Select::make('target_durattion')
                                        ->label('Target Duration')
                                        ->options([
                                            'hourly' => 'Hourly',
                                            'daily' => 'Daily',
                                        ]),

                                    TextInput::make('target')
                                        ->label('Target')
                                        ->numeric(),
                                        
                                    Select::make('measurement_unit')
                                        ->label('Measurement Unit')
                                        ->options([
                                            'pcs' => 'Pieces',
                                            'kgs' => 'Kilograms',
                                            'liters' => 'Liters',
                                            'minutes' => 'Minutes',
                                            'hours' => 'Hours',
                                            ]),
                                ])
                                ->itemLabel(fn (array $state): ?string => ($state['workstation_name'] ?? '') . ' - ' . ($state['operation_description'] ?? ''))
                                ->disableItemCreation()
                                ->reorderable()
                                ->reorderableWithButtons()
                                ->columns(5)
                                ->default([])
                                ->deleteAction(
                                    fn (Forms\Components\Actions\Action $action) => $action->after(function ($get, $set) {
                                        $operations = $get('daily_operations') ?? [];
                                        foreach ($operations as $index => &$operation) {
                                            $operation['sequence'] = $index + 1;
                                        }
                                        $set('daily_operations', array_values($operations));
                                    })
                                )
                                ->columnSpanFull(), 
                        ]),
                    ]),
                ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Sequence ID')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('order_type'),
                Tables\Columns\TextColumn::make('order_id')->sortable(),
                Tables\Columns\TextColumn::make('operation_date')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_type')
                    ->options([
                        'Customer Order' => 'Customer Order',
                        'Sample Order' => 'Sample Order',
                    ])
                    ->label('Filter by Order Type'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('id', 'desc');
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
