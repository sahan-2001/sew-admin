<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnterPerformanceRecordResource\Pages;
use App\Filament\Resources\EnterPerformanceRecordResource\RelationManagers;
use App\Models\EnterPerformanceRecord;
use App\Models\ProductionLine;
use App\Models\Workstation;
use App\Models\CustomerOrder;
use App\Models\SampleOrder;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions;
use Filament\Tables\Columns;
use Filament\Forms\Components\Tab;


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
                ->tabs([
                    Tabs\Tab::make('Select Operation')
                        ->schema([
                            DatePicker::make('operated_date')
                                ->label('Operated Date')
                                ->required()
                                ->reactive()
                                ->default(now())
                                ->afterStateUpdated(function (callable $get, callable $set) {
                                    // Clear operation_id and all metadata fields when date changes
                                    $set('operation_id', null);
                                    $set('meta.order_type', null);
                                    $set('meta.order_id', null);
                                    $set('meta.operation_date', null);
                                    $set('meta.machine_setup_time', null);
                                    $set('meta.machine_run_time', null);
                                    $set('meta.labor_setup_time', null);
                                    $set('meta.labor_run_time', null);
                                    $set('meta.target_duration', null);
                                    $set('meta.target', null);
                                    $set('meta.measurement_unit', null);
                                }),

                            Select::make('operation_type')
                                ->label('Operation Type')
                                ->options([
                                    'assigned' => 'Assigned Daily Operation',
                                    'um' => 'UM Operation',
                                    'temp' => 'Temporary Operation',
                                ])
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function (callable $get, callable $set) {
                                    // Clear operation_id and all metadata fields when operation type changes
                                    $set('operation_id', null);
                                    $set('meta.order_type', null);
                                    $set('meta.order_id', null);
                                    $set('meta.operation_date', null);
                                    $set('meta.machine_setup_time', null);
                                    $set('meta.machine_run_time', null);
                                    $set('meta.labor_setup_time', null);
                                    $set('meta.labor_run_time', null);
                                    $set('meta.target_duration', null);
                                    $set('meta.target', null);
                                    $set('meta.measurement_unit', null);
                                }),

                            Select::make('operation_id')
    ->label('Operation')
    ->reactive()
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
            $set('meta.order_type', $operationType);
            $set('meta.order_id', $model->operation->order_id ?? $model->order_id ?? null);
            $set('meta.operation_date', $model->operation->operation_date ?? $model->operation_date ?? null);
            $set('meta.machine_setup_time', $model->machine_setup_time ?? 0);
            $set('meta.machine_run_time', $model->machine_run_time ?? 0);
            $set('meta.labor_setup_time', $model->labor_setup_time ?? 0);
            $set('meta.labor_run_time', $model->labor_run_time ?? 0);
            $set('meta.target_duration', $model->target_duration ?? null);
            $set('meta.target', $model->target ?? null);
            $set('meta.measurement_unit', $model->measurement_unit ?? null);
        }
    }),


                        ]),



                    Tabs\Tab::make('Operation Metadata')
                        ->schema([
                            TextInput::make('meta.order_type')->label('Order Type')->disabled(),
                            TextInput::make('meta.order_id')->label('Order ID')->disabled(),
                            TextInput::make('meta.operation_date')->label('Operation Date')->disabled(),
                            
                            TextInput::make('meta.machine_setup_time')->label('Machine Setup Time')->disabled(),
                            TextInput::make('meta.machine_run_time')->label('Machine Run Time')->disabled(),
                            TextInput::make('meta.labor_setup_time')->label('Labor Setup Time')->disabled(),
                            TextInput::make('meta.labor_run_time')->label('Labor Run Time')->disabled(),
                            TextInput::make('meta.target_duration')->label('Target Duration')->disabled(),
                            TextInput::make('meta.target')->label('Target')->disabled(),
                            TextInput::make('meta.measurement_unit')->label('Measurement Unit')->disabled(),
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