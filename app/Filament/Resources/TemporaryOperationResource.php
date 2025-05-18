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

class TemporaryOperationResource extends Resource
{
    protected static ?string $model = TemporaryOperation::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Temporary Operations';
    protected static ?string $navigationGroup = 'Production Management';

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
                                    ->options(function ($get) {
                                        if ($get('order_type') === 'customer_order') {
                                            return CustomerOrder::pluck('name', 'order_id');
                                        } elseif ($get('order_type') === 'sample_order') {
                                            return SampleOrder::pluck('name', 'order_id');
                                        }
                                        return [];
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $set('customer_id', null);
                                        $set('wanted_date', null);

                                        $orderType = $get('order_type');
                                        if ($orderType === 'customer_order') {
                                            $order = CustomerOrder::find($state);
                                        } elseif ($orderType === 'sample_order') {
                                            $order = SampleOrder::find($state);
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

                    

                Section::make('Production Details')
                    ->schema([
                                Textarea::make('description')
                                    ->label('Operation Description')
                                    ->nullable()
                                    ->columns(1)
                                    ->columnSpan('full')
                                    ->required(),

                                Select::make('production_line_id')
                                    ->label('Production Line')
                                    ->options(ProductionLine::all()->pluck('name', 'id'))
                                    ->columns(1)
                                    ->reactive()
                                    ->dehydrated(),

                                Select::make('workstation_id')
                                    ->label('Workstation')
                                    ->options(Workstation::all()->pluck('name', 'id'))
                                    ->columns(1)
                                    ->reactive()
                                    ->dehydrated(),

                                TextInput::make('setup_time')
                                    ->label('Setup Time')
                                    ->numeric()
                                    ->default(0)
                                    ->columns(1),

                                TextInput::make('run_time')
                                    ->label('Run Time')
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->columns(1),

                                Forms\Components\MultiSelect::make('employee_ids')
                                    ->label('Employees')
                                    ->options(\App\Models\User::role('employee')->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->columns(1),

                                Forms\Components\MultiSelect::make('supervisor_ids')
                                    ->label('Supervisors')
                                    ->options(\App\Models\User::role('supervisor')->pluck('name', 'id'))
                                    ->searchable()
                                    ->columns(1),

                                Forms\Components\MultiSelect::make('machine_ids')
                                    ->label('Machines')
                                    ->options(\App\Models\ProductionMachine::pluck('name', 'id'))
                                    ->searchable()
                                    ->columns(1),

                                Forms\Components\MultiSelect::make('third_party_service_ids')
                                    ->label('Third Party Services')
                                    ->options(\App\Models\ThirdPartyService::pluck('name', 'id'))
                                    ->searchable()
                                    ->columns(1),
                    ])
                    ->columns(1),

        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('description')->sortable(),
                TextColumn::make('setup_time')->sortable(),
                TextColumn::make('run_time')->sortable(),
                TextColumn::make('created_at')->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
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
