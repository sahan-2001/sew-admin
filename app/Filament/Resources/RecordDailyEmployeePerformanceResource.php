<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecordDailyEmployeePerformanceResource\Pages;
use App\Filament\Resources\RecordDailyEmployeePerformanceResource\RelationManagers;
use App\Models\RecordDailyEmployeePerformance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;

class RecordDailyEmployeePerformanceResource extends Resource
{
    protected static ?string $model = RecordDailyEmployeePerformance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Employee Performance')
                    ->tabs([
                        Tabs\Tab::make('Order & Operation Details')
                        ->schema([
                            Section::make('Operation Date')
                            ->schema([
                                DatePicker::make('operation_date')
                                    ->label('Operation Date')
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now()) 
                                    ->columnSpan(1)
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('order_type', null);
                                        $set('order_id', null);
                                        $set('customer_id', null);
                                        $set('wanted_date', null);
                                    }),
                            ])->columns(1),

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
                                                    $set('production_line_id', null);
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
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    $set('customer_id', null);
                                                    $set('wanted_date', null);

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
                    Select::make('operation_type')
                        ->label('Operation Type')
                        ->required()
                        ->options([
                            'assigned' => 'Assigned Daily Operation',
                            'temporary' => 'Temporary Operation',
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            $set('operation_id', null);
                            $set('operation_lines', []); // Clear previous lines
                            
                            $operationDate = $get('operation_date');
                            $orderType = $get('order_type');
                            $orderId = $get('order_id');
                            
                            if ($operationDate && $orderType && $orderId) {
                                if ($state === 'assigned') {
                                    $operation = \App\Models\AssignDailyOperation::where('operation_date', $operationDate)
                                        ->where('order_type', $orderType)
                                        ->where('order_id', $orderId)
                                        ->first();
                                } else {
                                    $operation = \App\Models\TemporaryOperation::where('operation_date', $operationDate)
                                        ->where('order_type', $orderType)
                                        ->where('order_id', $orderId)
                                        ->first();
                                }
                                
                                if ($operation) {
                                    $set('operation_id', $operation->id);
                                    // Load the lines immediately when operation is found
                                    $this->loadOperationLines($state, $operation->id, $set);
                                }
                            }
                        }),
                        
                    Select::make('operation_id')
                        ->label('Operation ID')
                        ->options(function ($get) {
                            $operationType = $get('operation_type');
                            $operationDate = $get('operation_date');
                            $orderType = $get('order_type');
                            $orderId = $get('order_id');
                            
                            if (!$operationType || !$operationDate || !$orderType || !$orderId) {
                                return [];
                            }
                            
                            if ($operationType === 'assigned') {
                                $operation = \App\Models\AssignDailyOperation::where('operation_date', $operationDate)
                                    ->where('order_type', $orderType)
                                    ->where('order_id', $orderId)
                                    ->first();
                                
                                return $operation ? [$operation->id => 'Assigned Operation #' . $operation->id] : [];
                            } else {
                                $operation = \App\Models\TemporaryOperation::where('operation_date', $operationDate)
                                    ->where('order_type', $orderType)
                                    ->where('order_id', $orderId)
                                    ->first();
                                
                                return $operation ? [$operation->id => 'Temporary Operation #' . $operation->id] : [];
                            }
                        })
                        ->disabled(fn ($get) => !$get('operation_type'))
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if ($state) {
                                $operationType = $get('operation_type');
                                $this->loadOperationLines($operationType, $state, $set);
                            }
                        }),
                        
                    // Section to display operation lines
                    Section::make('Operation Lines')
                        ->schema([
                            Repeater::make('operation_lines')
                                ->schema([
                                    TextInput::make('product_id')
                                        ->label('Product ID')
                                        ->disabled(),
                                    TextInput::make('product_name')
                                        ->label('Product Name')
                                        ->disabled(),
                                    TextInput::make('quantity')
                                        ->label('Quantity')
                                        ->disabled(),
                                    // Add more fields as needed based on your line structure
                                ])
                                ->disabled()
                                ->columns(3)
                                ->visible(fn ($get) => !empty($get('operation_lines')))
                        ])
        ]),
                        ]),

                        Tabs\Tab::make('Performance Details')
                            ->schema([
                                TextInput::make('task')
                                    ->label('Task')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('output_quantity')
                                    ->label('Output Quantity')
                                    ->numeric(),
                            ]),

                        Tabs\Tab::make('Remarks')
                            ->schema([
                                Textarea::make('remarks')
                                    ->label('Remarks')
                                    ->rows(5),
                            ]),
                    ])
                    ->columnspanFull(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListRecordDailyEmployeePerformances::route('/'),
            'create' => Pages\CreateRecordDailyEmployeePerformance::route('/create'),
            'edit' => Pages\EditRecordDailyEmployeePerformance::route('/{record}/edit'),
        ];
    }
}
