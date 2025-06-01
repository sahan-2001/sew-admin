<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CuttingRecordResource\Pages;
use App\Filament\Resources\CuttingRecordResource\RelationManagers;
use App\Models\CuttingRecord;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Tab;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Group;
use App\Models\CuttingStation;
use App\Models\InventoryItem;
use App\Models\InventoryLocation;
use App\Models\NonInventoryItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;



class CuttingRecordResource extends Resource
{
    protected static ?string $model = CuttingRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Cutting Performance')
                    ->columnSpanFull()
                    ->tabs([
                        // Order Tab
                        Tabs\Tab::make('Order')
                            ->schema([
                                Section::make('Cutting Station Details')
                                    ->columns(2)
                                    ->schema([
                                        DatePicker::make('operation_date')
                                            ->label('Operation Date')
                                            ->required()
                                            ->default(now())
                                            ->maxDate(now()),
                                            
                                        Select::make('cutting_station_id')
                                            ->label('Cutting Station')
                                            ->required()
                                            ->options(CuttingStation::all()->pluck('name', 'id'))
                                            ->searchable()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                $station = CuttingStation::find($state);
                                                if ($station) {
                                                    $set('station_description', $station->description);
                                                }
                                            }),
                                        
                                        TextInput::make('station_description')
                                            ->label('Cutting Station Name')
                                            ->disabled()
                                            ->columnSpanFull(),
                                            
                                        ]),

                                            
                                Section::make('Order Details')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('order_type')
                                            ->label('Order Type')
                                            ->required()
                                            ->options([
                                                'customer_order' => 'Customer Order',
                                                'sample_order' => 'Sample Order',
                                            ])
                                            ->reactive(),

                                        Select::make('order_id')
                                            ->label('Order')
                                            ->required()
                                            ->searchable()
                                            ->reactive()
                                            ->options(function (callable $get) {
                                                $orderType = $get('order_type');

                                                if ($orderType === 'customer_order') {
                                                    return \App\Models\CustomerOrder::pluck('name', 'order_id');
                                                } elseif ($orderType === 'sample_order') {
                                                    return \App\Models\SampleOrder::pluck('name', 'order_id');
                                                }

                                                return [];
                                            })
                                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                                $orderType = $get('order_type');

                                                if ($orderType === 'customer_order') {
                                                    $order = \App\Models\CustomerOrder::find($state);
                                                } elseif ($orderType === 'sample_order') {
                                                    $order = \App\Models\SampleOrder::find($state);
                                                } else {
                                                    $order = null;
                                                }

                                                $set('customer_id', $order->customer_id ?? 'N/A');
                                                $set('wanted_date', $order->wanted_delivery_date ?? 'N/A');
                                            }),

                                        TextInput::make('customer_id')
                                            ->label('Customer ID')
                                            ->disabled(),

                                        DatePicker::make('wanted_date')
                                            ->label('Wanted Delivery Date')
                                            ->disabled(),
                                    ]),
                                    
                                Section::make('Operation Time')
                                    ->columns(3)
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
                                            ->columnSpan(1),
                                    ]),
                            ]),
                            
                        // Employees Tab
                        Tabs\Tab::make('Employees')
                            ->schema([
                                Repeater::make('employees')
                                    ->relationship()
                                    ->label('Cutting Employees')
                                    ->schema([
                                        Select::make('employee_id')
                                            ->label('Employee')
                                            ->required()
                                            ->searchable()
                                            ->options(function (callable $get, $state) {
                                                $selectedUserIds = collect($get('../../employees'))
                                                    ->pluck('user_id')
                                                    ->filter()
                                                    ->reject(fn($id) => $id === $state)
                                                    ->unique();

                                                return \App\Models\User::role('employee')
                                                    ->whereNotIn('id', $selectedUserIds)
                                                    ->pluck('name', 'id');
                                            }),
                                            
                                        TextInput::make('pieces_cut')
                                            ->label('Pieces Cut')
                                            ->numeric()
                                            ->required()
                                            ->reactive()
                                            ->default(0),
                                            
                                        Select::make('supervisor_id')
                                            ->label('Supervisor')
                                            ->searchable()
                                            ->options(
                                                \App\Models\User::role('supervisor')->pluck('name', 'id')
                                            ),
                                            
                                        Textarea::make('notes')
                                            ->label('Notes')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(3)
                                    ->columnSpanFull(),

                                    Section::make('Summary')
                                    ->schema([
                                        Placeholder::make('total_pieces_cut')
                                            ->label('Total Cut Pieces')
                                            ->content(function (callable $get, callable $set) {
                                                $details = $get('employees') ?? [];

                                                $total = collect($details)->sum(function ($item) {
                                                    return (int) ($item['pieces_cut'] ?? 0);  
                                                });

                                                $set('total_pieces_cut', $total); 
                                                return $total;
                                            })
                                            ->reactive()
                                            ->live(),
                                        ]),
                            ]),
                            
                        
                            
                        // Waste Tab
                        Tabs\Tab::make('Waste')
                            ->schema([
                                Section::make('Material Waste')
                                    ->schema([
                                        Repeater::make('waste_records')
                                            ->label('Inventory Waste')
                                            ->schema([
                                                Select::make('inv_item_id')
                                                    ->label('Waste Item')
                                                    ->options(InventoryItem::where('category', 'Waste Item')->pluck('name', 'id'))
                                                    ->searchable()
                                                    ->reactive(),

                                                TextInput::make('inv_amount')
                                                    ->label('Amount')
                                                    ->numeric()
                                                    ->required(fn (callable $get) => filled($get('inv_item_id'))),
                                                    
                                                Select::make('inv_unit')
                                                    ->label('Unit')
                                                    ->options([
                                                        'pcs' => 'Pieces',
                                                        'kgs' => 'Kilograms',
                                                        'liters' => 'Liters',
                                                        'meters' => 'Meters',
                                                    ])
                                                    ->required(fn (callable $get) => filled($get('inv_item_idd'))),
                                                    
                                                Select::make('inv_location_id')
                                                    ->label('Location')
                                                    ->options(InventoryLocation::where('location_type', 'picking')->pluck('name', 'id'))
                                                    ->searchable()
                                                    ->required(fn (callable $get) => filled($get('inv_item_id'))),
                                            ])
                                            ->columns(4),
                                            
                                        Repeater::make('non_inventory_waste')
                                            ->label('Non-Inventory Waste')
                                            ->schema([
                                                Select::make('non_i_item_id')
                                                    ->label('Item')
                                                    ->options(NonInventoryItem::pluck('name', 'id'))
                                                    ->searchable()
                                                    ->reactive(),

                                                TextInput::make('non_i_amount')
                                                    ->label('Amount')
                                                    ->numeric()
                                                    ->required(fn (callable $get) => filled($get('non_i_item_id'))),
                                                    
                                                Select::make('non_i_unit')
                                                    ->label('Unit')
                                                    ->options([
                                                        'minutes' => 'Minutes',
                                                        'hours' => 'Hours',
                                                    ])
                                                    ->required(fn (callable $get) => filled($get('non_i_item_id'))),
                                            ])
                                            ->columns(3),
                                    ]),
                            ]),
                            
                        // Quality Control Tab
                        Tabs\Tab::make('Quality Control')
                            ->schema([
                                Section::make('Quality Inspection')
                                    ->schema([
                                        Repeater::make('qualityControls') 
                                            ->relationship()
                                            ->schema([
                                                Select::make('qc_user_id')
                                                    ->label('Quality Control Officer')
                                                    ->required()
                                                    ->searchable()
                                                    ->options(function (callable $get, $state) {
                                                        $selectedUserIds = collect($get('../../qualityControls'))
                                                            ->pluck('qc_user_id')
                                                            ->filter()
                                                            ->reject(fn ($id) => $id === $state) 
                                                            ->unique();

                                                        return \App\Models\User::role('quality control')
                                                            ->whereNotIn('id', $selectedUserIds)
                                                            ->pluck('name', 'id');
                                                    }),


                                                TextInput::make('inspected_pieces')
                                                    ->label('Inspected Pieces')
                                                    ->numeric()
                                                    ->default(0),
                                                
                                                TextInput::make('accepted_pieces')
                                                    ->label('Accepted Pieces')
                                                    ->numeric()
                                                    ->default(0),

                                                Textarea::make('notes')
                                                    ->label('Notes')
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull(),
                                        ]),
                            ]),

                        // Cut Piece Label Tab
                        Tabs\Tab::make('Cut Piece Labels')
    ->schema([
        Section::make('Label Information')
            ->description('Track cut pieces with label ranges to minimize storage')
            ->schema([
                Placeholder::make('cut_pieces')
                    ->label('Total Cut Pieces')
                    ->content(fn (callable $get) => $get('total_pieces_cut') ?: 0)
                    ->reactive(),
                
                
                Fieldset::make('Label Range')
                    ->schema([
                        TextInput::make('start_label')
                            ->label('Start Label')
                            ->required()
                            ->helperText('Format: PREFIX-001'),
                            
                        TextInput::make('end_label')
                            ->label('End Label')
                            ->required()
                            ->helperText('Format: PREFIX-999'),
                            
                        TextInput::make('no_of_pieces')
                            ->label('Number of Pieces')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(3),
                    
                Textarea::make('label_notes')
                    ->label('Label Notes')
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
                Tables\Columns\TextColumn::make('operation_date')
                    ->label('Date')
                    ->date(),
                    
                Tables\Columns\TextColumn::make('cuttingStation.name')
                    ->label('Station'),
                    
                Tables\Columns\TextColumn::make('order_type')
                    ->label('Order Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'customer_order' => 'Customer',
                        'sample_order' => 'Sample',
                        'internal' => 'Internal',
                        default => $state,
                    }),
                    
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order ID'),
                    
                Tables\Columns\TextColumn::make('total_pieces')
                    ->label('Pieces'),
                    
                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Operators')
                    ->counts('employees'),
            ])
            ->filters([
                
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
            'index' => Pages\ListCuttingRecords::route('/'),
            'create' => Pages\CreateCuttingRecord::route('/create'),
            'edit' => Pages\EditCuttingRecord::route('/{record}/edit'),
        ];
    }
}
