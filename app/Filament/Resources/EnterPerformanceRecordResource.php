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
use Filament\Forms\Components\{Select, Textarea, Grid, Section, Repeater, TextInput, Hidden};
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
                    Tab::make('Order Details')
                        ->schema([
                            // Order type and order ID selection
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

                            // Operation selection
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
                                                    
                                                    switch ($operationType) {
                                                        case 'assigned':
                                                            return AssignDailyOperationLine::with([
                                                                    'productionLine', 
                                                                    'workstation', 
                                                                    'operation',
                                                                    'assignDailyOperation'
                                                                ])
                                                                ->whereHas('assignDailyOperation', function($query) use ($orderType, $orderId) {
                                                                    $query->where('order_type', $orderType)
                                                                        ->where('order_id', $orderId);
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
                                                                ->whereHas('umOperation', function($query) use ($orderType, $orderId) {
                                                                    $query->where('order_type', $orderType)
                                                                        ->where('order_id', $orderId);
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
                                    
                                    // Add operation button
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
                                                
                                                // Get existing records
                                                $existingRecords = $get('performance_records') ?? [];
                                                
                                                // Check if operation already exists
                                                $exists = collect($existingRecords)->contains(function ($record) use ($operationType, $operationId) {
                                                    return $record['type'] === $operationType && $record['id'] == $operationId;
                                                });
                                                
                                                if ($exists) {
                                                    return; // Operation already added
                                                }
                                                
                                                // Fetch operation details based on type
                                                $operationDetails = [];
                                                
                                                switch ($operationType) {
                                                    case 'assigned':
                                                        $operation = AssignDailyOperationLine::with(['productionLine', 'workstation', 'operation'])->find($operationId);
                                                        if ($operation) {
                                                            $operationDetails = [
                                                                'type' => 'assigned',
                                                                'id' => $operationId,
                                                                'operation_name' => $operation->operation->name ?? 'N/A',
                                                                'production_line' => $operation->productionLine->name ?? 'N/A',
                                                                'workstation' => $operation->workstation->name ?? 'N/A',
                                                            ];
                                                        }
                                                        break;
                                                    
                                                    case 'um':
                                                        $operation = UMOperationLine::with(['productionLine', 'workstation', 'operation'])->find($operationId);
                                                        if ($operation) {
                                                            $operationDetails = [
                                                                'type' => 'um',
                                                                'id' => $operationId,
                                                                'operation_name' => $operation->operation->name ?? 'N/A',
                                                                'production_line' => $operation->productionLine->name ?? 'N/A',
                                                                'workstation' => $operation->workstation->name ?? 'N/A',
                                                            ];
                                                        }
                                                        break;
                                                    
                                                    case 'temp':
                                                        $operation = TemporaryOperation::with(['productionLine', 'workstation'])->find($operationId);
                                                        if ($operation) {
                                                            $operationDetails = [
                                                                'type' => 'temp',
                                                                'id' => $operationId,
                                                                'operation_name' => $operation->description ?? 'No description',
                                                                'production_line' => $operation->productionLine->name ?? 'N/A',
                                                                'workstation' => $operation->workstation->name ?? 'N/A',
                                                            ];
                                                        }
                                                        break;
                                                }
                                                
                                                if (!empty($operationDetails)) {
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

                            // Performance records table
                            Section::make('Performance Records')
                                ->schema([
                                    Repeater::make('performance_records')
                                        ->schema([
                                            Grid::make(4)
                                                ->schema([
                                                    // Operation Info
                                                    TextInput::make('operation_name')
                                                        ->label('Operation')
                                                        ->disabled(),
                                                    TextInput::make('production_line')
                                                        ->label('Production Line')
                                                        ->disabled(),
                                                    TextInput::make('workstation')
                                                        ->label('Workstation')
                                                        ->disabled(),
                                                    
                                                    // Operation Details from AssignDailyOperationLine
                                                    TextInput::make('machine_setup_time')
                                                        ->label('Machine Setup (min)')
                                                        ->disabled()
                                                        ->visible(fn ($get) => $get('type') === 'assigned'),
                                                    TextInput::make('machine_run_time')
                                                        ->label('Machine Run (min)')
                                                        ->disabled()
                                                        ->visible(fn ($get) => $get('type') === 'assigned'),
                                                    TextInput::make('labor_setup_time')
                                                        ->label('Labor Setup (min)')
                                                        ->disabled()
                                                        ->visible(fn ($get) => $get('type') === 'assigned'),
                                                    TextInput::make('labor_run_time')
                                                        ->label('Labor Run (min)')
                                                        ->disabled()
                                                        ->visible(fn ($get) => $get('type') === 'assigned'),
                                                    TextInput::make('target_duration')
                                                        ->label('Target Duration')
                                                        ->disabled()
                                                        ->visible(fn ($get) => $get('type') === 'assigned'),
                                                    TextInput::make('target')
                                                        ->label('Target Quantity')
                                                        ->disabled()
                                                        ->visible(fn ($get) => $get('type') === 'assigned'),
                                                    TextInput::make('measurement_unit')
                                                        ->label('Measurement Unit')
                                                        ->disabled()
                                                        ->visible(fn ($get) => $get('type') === 'assigned'),
                                                    
                                                    // Performance Inputs
                                                    TextInput::make('actual_quantity')
                                                        ->label('Actual Quantity')
                                                        ->numeric()
                                                        ->required(),
                                                    TextInput::make('actual_time')
                                                        ->label('Actual Time (min)')
                                                        ->numeric()
                                                        ->required(),
                                                        
                                                    Hidden::make('type'),
                                                    Hidden::make('id'),
                                                    
                                                    // Remove button
                                                    Forms\Components\Actions::make([
                                                        Forms\Components\Actions\Action::make('remove_operation')
                                                            ->icon('heroicon-o-trash')
                                                            ->color('danger')
                                                            ->action(function ($get, $set, $index) {
                                                                $records = $get('performance_records');
                                                                array_splice($records, $index, 1);
                                                                $set('performance_records', $records);
                                                            }),
                                                    ]),
                                                ]),
                                        ])
                                        ->columns(1)
                                        ->default([]),
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
