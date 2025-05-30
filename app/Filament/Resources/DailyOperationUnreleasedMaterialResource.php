<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyOperationUnreleasedMaterialResource\Pages;
use App\Filament\Resources\DailyOperationUnreleasedMaterialResource\RelationManagers;
use App\Models\UMOperation;
use App\Models\UMOperationLine;
use App\Models\UMOperationLineEmployee;
use App\Models\UMOperationLineSupervisor;
use App\Models\UMOperationLineMachine;
use App\Models\UMOperationLineService;
use App\Models\ProductionLine;
use App\Models\ReleaseMaterial;
use App\Models\ProductionMachine;
use App\Models\ThirdPartyService;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use App\Models\User;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Filament\Forms\Components\ButtonAction;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions\Button;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;

class DailyOperationUnreleasedMaterialResource extends Resource
{
    protected static ?string $model = UMOperation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Daily Production';
    protected static ?string $navigationLabel = 'Operations With Unreleased Materials';
    protected static ?string $modelLabel = 'Daily Operation (Unreleased Materials)';
    protected static ?string $slug = 'unreleased-operations';

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
                                $set('order_id', null);
                                $set('customer_id', null);
                                $set('wanted_date', null);
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
                                    return \App\Models\CustomerOrder::where('status', '!=', 'planned')
                                        ->pluck('name', 'order_id');
                                } elseif ($orderType === 'sample_order') {
                                    return \App\Models\SampleOrder::where('status', '!=', 'planned')
                                        ->pluck('name', 'order_id');
                                }
                                return [];
                            })
                            ->reactive()
                            ->hidden(fn ($get) => !$get('order_type')) 
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $set('customer_id', null);
                                $set('wanted_date', null);
                                $set('daily_operations', []);

                                $orderType = $get('order_type');
                                if ($orderType && $state) {
                                    // Check if materials have been released
                                    $hasReleasedMaterials = ReleaseMaterial::where('order_type', $orderType)
                                        ->where('order_id', $state)
                                        ->exists();
                                    
                                    if ($hasReleasedMaterials) {
                                        Notification::make()
                                            ->title('Materials Already Released')
                                            ->body('Materials have been released for this order. Please use the "Operations With Released Materials" feature.')
                                            ->danger()
                                            ->send();
                                        
                                        // Clear all fields
                                        $set('order_id', null);
                                        $set('customer_id', null);
                                        $set('wanted_date', null);
                                        return;
                                    }

                                    // Get order details
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
                
            Section::make('Add New Operation')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Select::make('production_line_id')
                                ->label('Production Line')
                                ->options(ProductionLine::all()->pluck('name', 'id'))
                                ->required(fn ($livewire) => get_class($livewire) === \App\Filament\Resources\DailyOperationUnreleasedMaterialResource\Pages\CreateDailyOperationUnreleasedMaterial::class)
                                ->reactive()
                                ->searchable(),

                            Select::make('workstation_id')
                                ->label('Workstation')
                                ->options(function ($get) {
                                    if (!$get('production_line_id')) {
                                        return Workstation::all()->pluck('name', 'id');
                                    }
                                    return Workstation::where('production_line_id', $get('production_line_id'))
                                        ->pluck('name', 'id');
                                })
                                ->required(fn ($livewire) => get_class($livewire) === \App\Filament\Resources\DailyOperationUnreleasedMaterialResource\Pages\CreateDailyOperationUnreleasedMaterial::class)
                                ->reactive()
                                ->searchable(),

                            Select::make('operation_id')
                                ->label('Operation')
                                ->options(function ($get) {
                                    if (!$get('workstation_id')) {
                                        return Operation::all()->pluck('description', 'id');
                                    }
                                    return Operation::where('workstation_id', $get('workstation_id'))
                                        ->pluck('description', 'id');
                                })
                                ->required(fn ($livewire) => get_class($livewire) === \App\Filament\Resources\DailyOperationUnreleasedMaterialResource\Pages\CreateDailyOperationUnreleasedMaterial::class)
                                ->reactive()
                                ->searchable()
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    $operation = Operation::find($state);
                                    if ($operation) {
                                        $set('selected_operation_description', $operation->description);
                                        $set('selected_machine__setup_time', $operation->machine_setup_time);
                                        $set('selected_machine_run_time', $operation->machine_run_time);
                                        $set('selected_labor__setup_time', $operation->labor_setup_time);
                                        $set('selected_labor_run_time', $operation->labor_run_time);
                                    }
                                }),
                        ]),

                    Grid::make(3)
                        ->schema([
                            TextInput::make('selected_machine__setup_time')
                                ->label('Machine Setup Time (minutes)')
                                ->numeric()
                                ->default(0)
                                ->hidden(),

                            TextInput::make('selected_machine_run_time')
                                ->label('Machine Run Time (minutes)')
                                ->numeric()
                                ->default(0)
                                ->hidden(),

                            TextInput::make('selected_labor_setup_time')
                                ->label('Labor Setup Time (minutes)')
                                ->numeric()
                                ->required(fn ($livewire) => get_class($livewire) === \App\Filament\Resources\DailyOperationUnreleasedMaterialResource\Pages\CreateDailyOperationUnreleasedMaterial::class)
                                ->disabled(fn ($livewire) => get_class($livewire) === \App\Filament\Resources\DailyOperationUnreleasedMaterialResource\Pages\EditDailyOperationUnreleasedMaterial::class)
                                ->default(0)
                                ->disabled(),

                            TextInput::make('selected_labor_run_time')
                                ->label('Labor Run Time (minutes)')
                                ->numeric()
                                ->required(fn ($livewire) => get_class($livewire) === \App\Filament\Resources\DailyOperationUnreleasedMaterialResource\Pages\CreateDailyOperationUnreleasedMaterial::class)
                                ->disabled(fn ($livewire) => get_class($livewire) === \App\Filament\Resources\DailyOperationUnreleasedMaterialResource\Pages\EditDailyOperationUnreleasedMaterial::class)
                                ->default(0)
                                ->disabled(),

                            Forms\Components\Actions::make([
                                Action::make('add_operation')
                                    ->label('Add Operation')
                                    ->button()
                                    ->action(function ($get, $set) {
                                        $productionLineId = $get('production_line_id');
                                        $workstationId = $get('workstation_id');
                                        $operationId = $get('operation_id');
                                        
                                        if (!$productionLineId || !$workstationId || !$operationId) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('Production Line, Workstation, and Operation must be selected')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        $operation = Operation::find($operationId);
                                        $workstation = Workstation::find($workstationId);
                                        $productionLine = ProductionLine::find($productionLineId);
                                        
                                        if (!$operation || $operation->status === 'inactive') {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('The selected operation is inactive and cannot be added.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        
                                        $currentOperations = $get('daily_operations') ?? [];
                                        
                                        if (collect($currentOperations)->contains('operation_id', $operationId)) {
                                            Notification::make()
                                                ->title('Warning')
                                                ->body('This operation is already added')
                                                ->warning()
                                                ->send();
                                            return;
                                        }
                                        
                                        $sequence = count($currentOperations) + 1;

                                        $employeeIds = $operation->employee_id ? 
                                            (is_array($operation->employee_id) ? $operation->employee_id : [$operation->employee_id]) : [];
                                        $supervisorIds = $operation->supervisor_id ? 
                                            (is_array($operation->supervisor_id) ? $operation->supervisor_id : [$operation->supervisor_id]) : [];
                                        $machineIds = $operation->machine_id ? 
                                            (is_array($operation->machine_id) ? $operation->machine_id : [$operation->machine_id]) : [];
                                        $thirdPartyServiceIds = $operation->third_party_service_id ? 
                                            (is_array($operation->third_party_service_id) ? $operation->third_party_service_id : [$operation->third_party_service_id]) : [];

                                        $newOperation = [
                                            'production_line_id' => $productionLineId,
                                            'production_line_name' => $productionLine->name ?? 'N/A',
                                            'workstation_id' => $workstationId,
                                            'workstation_name' => $workstation->name ?? 'N/A',
                                            'operation_id' => $operationId,
                                            'operation_description' => $operation->description ?? 'N/A',
                                            'sequence' => $sequence,
                                            'status' => $operation->status ?? 'active',
                                            'machine_setup_time' => $get('selected_machine_setup_time') ?? $operation->machine_setup_time ?? 0,
                                            'machine_run_time' => $get('selected_machine_run_time') ?? $operation->machine_run_time ?? 0,
                                            'labor_setup_time' => $get('selected_labor_setup_time') ?? $operation->labor_setup_time ?? 0,
                                            'labor_run_time' => $get('selected_labor_run_time') ?? $operation->labor_run_time ?? 0,
                                            'employee_ids' => $employeeIds,
                                            'supervisor_ids' => $supervisorIds,
                                            'machine_ids' => $machineIds,
                                            'third_party_service_ids' => $thirdPartyServiceIds,
                                        ];
                                        
                                        $currentOperations[] = $newOperation;
                                        $set('daily_operations', $currentOperations);
                                    })
                                    ->icon('heroicon-o-plus'),
                            ]),
                        ]),
                ]),
                
            Section::make('Daily Operation Lines')
                ->schema([
                    Repeater::make('daily_operations')
                        ->label('Operations Sequence')
                        ->schema([
                            TextInput::make('production_line_name')
                                ->label('Production Line')
                                ->disabled()
                                ->reactive(),
                                
                            TextInput::make('workstation_name')
                                ->label('Workstation')
                                ->disabled()
                                ->reactive(),
                            
                            TextInput::make('operation_description')
                                ->label('Operation')
                                ->disabled()
                                ->reactive(),

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
                            
                            TextInput::make('machine_setup_time')
                                ->label('Machine Setup Time')
                                ->numeric()
                                ->default(0),
                                        
                            TextInput::make('labor_setup_time')
                                ->label('Labor Setup Time')
                                ->numeric()
                                ->default(0),

                            TextInput::make('machine_run_time')
                                ->label('Machine Run Time')
                                ->numeric()
                                ->default(0),
                                        
                            TextInput::make('labor_run_time')
                                ->label('Labor Run Time')
                                ->numeric()
                                ->default(0),
                                
                            Select::make('target_duration')
                                ->label('Target Duration')                               
                                ->options([
                                    'hourly' => 'Hourly',
                                    'daily' => 'Daily',
                                ]),
                                
                            TextInput::make('target')
                                ->label('Target Quantity')
                                ->numeric(),
                                
                            Select::make('measurement_unit')
                                ->label('Unit')
                                ->options([
                                    'pcs' => 'Pieces',
                                    'kgs' => 'Kilograms',
                                    'liters' => 'Liters',
                                    'minutes' => 'Minutes',
                                    'hours' => 'Hours',
                                ]),
                        ])
                        ->itemLabel(fn (array $state): ?string => 
                            ($state['production_line_name'] ?? '') . ' → ' . 
                            ($state['workstation_name'] ?? '') . ' → ' . 
                            ($state['operation_description'] ?? ''))
                        ->reorderable()
                        ->reorderableWithButtons()
                        ->columns(3)
                        ->default([])
                        ->disableItemCreation()
                        ->deleteAction(
                            fn (Forms\Components\Actions\Action $action) => $action->after(function ($get, $set) {
                                $operations = $get('daily_operations') ?? [];
                                foreach ($operations as $index => &$operation) {
                                    $operation['sequence'] = $index + 1;
                                }
                                $set('daily_operations', array_values($operations));
                            })
                        ),
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->date('Y-m-d')      
                    ->sortable(),
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
            // Add any relations if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyOperationUnreleasedMaterials::route('/'),
            'create' => Pages\CreateDailyOperationUnreleasedMaterial::route('/create'),
            'edit' => Pages\EditDailyOperationUnreleasedMaterial::route('/{record}/edit'),
        ];
    }

 
}