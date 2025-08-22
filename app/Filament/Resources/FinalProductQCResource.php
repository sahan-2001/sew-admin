<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinalProductQCResource\Pages;
use App\Models\FinalProductQC;
use App\Models\CuttingLabel;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;

class FinalProductQCResource extends Resource
{
    protected static ?string $model = FinalProductQC::class;
    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $navigationGroup = 'Quality Control';
    protected static ?string $label = 'Final Product QC';
    protected static ?string $pluralLabel = 'Final Product QCs';
    protected static ?string $navigationLabel = 'Final Product QC';
    protected static ?int $navigationSort = 38;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make('Order Details')
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
                                                    $set('customer_name', null);
                                                    $set('wanted_date', null);
                                                }),

                                            Select::make('order_id')
                                                ->label('Order')
                                                ->required()
                                                ->disabled(fn ($get, $record) => $record !== null)
                                                ->dehydrated()
                                                ->searchable()
                                                ->helperText('You can select only orders with "completed" or "Delivered" status.')
                                                ->options(function ($get) {
                                                    $orderType = $get('order_type');
                                                    $allowedStatuses = ['completed', 'delivered'];

                                                    if ($orderType === 'customer_order') {
                                                        return \App\Models\CustomerOrder::with('customer')
                                                            ->whereIn('status', $allowedStatuses)
                                                            ->get()
                                                            ->mapWithKeys(function ($order) {
                                                                $customerName = $order->customer->name ?? 'Unknown Customer';
                                                                $label = "Order ID - {$order->order_id} | Name - {$order->name} | Customer - {$customerName}";
                                                                return [$order->order_id => $label];
                                                            });
                                                    } elseif ($orderType === 'sample_order') {
                                                        return \App\Models\SampleOrder::with('customer')
                                                            ->whereIn('status', $allowedStatuses)
                                                            ->get()
                                                            ->mapWithKeys(function ($order) {
                                                                $customerName = $order->customer->name ?? 'Unknown Customer';
                                                                $label = "Order ID - {$order->order_id} | Name - {$order->name} | Customer - {$customerName}";
                                                                return [$order->order_id => $label];
                                                            });
                                                    }

                                                    return [];
                                                })
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    $set('customer_id', null);
                                                    $set('wanted_date', null);

                                                    $orderType = $get('order_type');
                                                    if ($orderType && $state) {
                                                        $order = null;

                                                        if ($orderType === 'customer_order') {
                                                            $order = \App\Models\CustomerOrder::find($state);
                                                        } elseif ($orderType === 'sample_order') {
                                                            $order = \App\Models\SampleOrder::find($state);
                                                        }

                                                        if (!$order || !in_array($order->status, ['cut', 'started'])) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Invalid Order Status')
                                                                ->body('Only orders with "Cut" or "Started" status can be selected.')
                                                                ->danger()
                                                                ->duration(8000)
                                                                ->send();

                                                            $set('order_id', null);
                                                            $set('customer_id', null);
                                                            $set('customer_name', null);
                                                            $set('wanted_date', null);
                                                            return;
                                                        }

                                                        $set('customer_id', $order->customer_id ?? 'N/A');
                                                        $set('customer_name', $order->customer->name?? 'N/A');
                                                        $set('wanted_date', $order->wanted_delivery_date ?? 'N/A');

                                                        $cuttingRecordExists = \App\Models\CuttingRecord::where('order_type', $orderType)
                                                            ->where('order_id', $state)
                                                            ->exists();

                                                        if (!$cuttingRecordExists) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('No Cutting Record Found')
                                                                ->body('The selected order does not have any Cutting Records.')
                                                                ->danger()
                                                                ->persistent()
                                                                ->send();

                                                            $set('order_id', null);
                                                            $set('customer_id', null);
                                                            $set('customer_name', null);
                                                            $set('wanted_date', null);
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
                                                            $set('customer_id', $order->customer->name ?? 'N/A');
                                                            $set('wanted_date', $order->wanted_delivery_date ?? 'N/A');
                                                        } else {
                                                            $set('customer_id', 'N/A');
                                                            $set('customer_name', 'N/A');
                                                            $set('wanted_date', 'N/A');
                                                        }
                                                    }
                                                }),

                                            TextInput::make('customer_id')
                                                ->label('Customer ID')
                                                ->disabled(),

                                            TextInput::make('customer_name')
                                                ->label('Customer Name')
                                                ->disabled(),

                                            TextInput::make('wanted_date')
                                                ->label('Wanted Date')
                                                ->disabled(),
                                        ]),
                                ]),     
                        ]),

                    Tabs\Tab::make('Quality Assurence Details')
                        ->schema([
                            Section::make('Quality Checker Information')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            Select::make('qc_officer_id')
                                                ->label('QC Officer')
                                                ->options(function () {
                                                    return User::role('Quality Control')
                                                        ->get()
                                                        ->mapWithKeys(function ($user) {
                                                            return [$user->id => "{$user->id} | {$user->name}"];
                                                        })
                                                        ->toArray();
                                                })
                                                ->searchable()
                                                ->required(),

                                            DatePicker::make('inspected_date')
                                                ->label('Inspected Date')
                                                ->default(now())
                                                ->required()
                                                ->maxDate(now()),
                                     ]),
                                ]),
                                    
                            Section::make('Quality Inspection Results')
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
                                                            $orderType = $get('order_type');
                                                            $orderId = $get('order_id');
                                                            $failedLabels = $get('selected_labels_qc_f') ?? [];
                                                            $labels = \App\Models\CuttingLabel::where('order_type', $orderType)
                                                                ->where('order_id', $orderId)
                                                                ->pluck('id')
                                                                ->toArray();
                                                            $availableLabels = array_diff($labels, $failedLabels);
                                                            $set('selected_labels_qc_p', $state ? $availableLabels : []);
                                                            if ($state) {
                                                                $set('range_start_label_qc_p', null);
                                                                $set('range_end_label_qc_p', null);
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
                                                                $set('range_start_label_qc_p', null);
                                                                $set('range_end_label_qc_p', null);
                                                            })
                                                    ])
                                                ]),

                                            Grid::make(2)
                                                ->schema([
                                                    Select::make('range_start_label_qc_p')
                                                        ->label('Start Label')
                                                        ->options(function (callable $get) {
                                                            $orderType = $get('order_type');
                                                            $orderId = $get('order_id');
                                                            return \App\Models\CuttingLabel::where('order_type', $orderType)
                                                                ->where('order_id', $orderId)
                                                                ->orderBy('label')
                                                                ->get()
                                                                ->mapWithKeys(function ($label) {
                                                                    return [
                                                                        $label->id => "{$label->id} | {$label->barcode_id}"
                                                                    ];
                                                                });
                                                        })
                                                        ->reactive()
                                                        ->searchable(),

                                                    Select::make('range_end_label_qc_p')
                                                        ->label('End Label')
                                                        ->options(function (callable $get) {
                                                            $orderType = $get('order_type');
                                                            $orderId = $get('order_id');
                                                            return \App\Models\CuttingLabel::where('order_type', $orderType)
                                                                ->where('order_id', $orderId)
                                                                ->orderBy('label')
                                                                ->get()
                                                                ->mapWithKeys(function ($label) {
                                                                    return [
                                                                        $label->id => "{$label->id} | {$label->barcode_id}"
                                                                    ];
                                                                });
                                                        })
                                                        ->reactive()
                                                        ->searchable(),
                                                    Actions::make([
                                                        Action::make('apply_range_qc_p')
                                                            ->label('Apply Label Range')
                                                            ->action(function (callable $get, callable $set) {
                                                                $orderType = $get('order_type');
                                                                $orderId = $get('order_id');
                                                                $startId = $get('range_start_label_qc_p');
                                                                $endId = $get('range_end_label_qc_p');
                                                                $failedLabels = $get('selected_labels_qc_f') ?? [];

                                                                if (!$orderType || !$orderId || !$startId || !$endId) {
                                                                    return;
                                                                }

                                                                $labels = \App\Models\CuttingLabel::where('order_type', $orderType)
                                                                    ->where('order_id', $orderId)
                                                                    ->orderBy('label')
                                                                    ->get();

                                                                $inRange = false;
                                                                $rangeIds = [];
                                                                foreach ($labels as $label) {
                                                                    if ($label->id == $startId || $label->id == $endId) {
                                                                        $rangeIds[] = $label->id;
                                                                        if ($startId !== $endId) {
                                                                            $inRange = !$inRange;
                                                                        } else {
                                                                            break;
                                                                        }
                                                                    } elseif ($inRange) {
                                                                        $rangeIds[] = $label->id;
                                                                    }
                                                                }
                                                                $rangeIds = array_diff($rangeIds, $failedLabels);
                                                                $existing = $get('selected_labels_qc_p') ?? [];
                                                                $set('selected_labels_qc_p', array_unique([...$existing, ...$rangeIds]));
                                                            })
                                                            ->color('primary'),
                                                    ])
                                                ]),

                                            Grid::make(1)
                                                ->schema([
                                                    CheckboxList::make('selected_labels_qc_p')
                                                        ->label('QC Passed Labels')
                                                        ->options(function (callable $get) {
                                                            $orderType = $get('order_type');
                                                            $orderId = $get('order_id');
                                                            $failedLabels = $get('selected_labels_qc_f') ?? [];
                                                            return \App\Models\CuttingLabel::where('order_type', $orderType)
                                                                ->where('order_id', $orderId)
                                                                ->get()
                                                                ->mapWithKeys(function ($label) use ($failedLabels) {
                                                                    if (in_array($label->id, $failedLabels)) {
                                                                        return [];
                                                                    }
                                                                    return [
                                                                        $label->id => "ID: {$label->id} | Barcode: {$label->barcode_id}"
                                                                    ];
                                                                });
                                                        })
                                                        ->columns(3)
                                                        ->reactive()
                                                        ->searchable()
                                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                            $set('selected_labels_qc_p', is_array($state) ? $state : []);
                                                        })
                                                        ->dehydrated(),
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
                                                            $orderType = $get('order_type');
                                                            $orderId = $get('order_id');
                                                            $passedLabels = $get('selected_labels_qc_p') ?? [];
                                                            $labels = \App\Models\CuttingLabel::where('order_type', $orderType)
                                                                ->where('order_id', $orderId)
                                                                ->pluck('id')
                                                                ->toArray();
                                                            $availableLabels = array_diff($labels, $passedLabels);
                                                            $set('selected_labels_qc_f', $state ? $availableLabels : []);
                                                            if ($state) {
                                                                $set('range_start_label_qc_f', null);
                                                                $set('range_end_label_qc_f', null);
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
                                                                $set('range_start_label_qc_f', null);
                                                                $set('range_end_label_qc_f', null);
                                                            })
                                                    ])
                                                ]),

                                            Grid::make(2)
                                                ->schema([
                                                    Select::make('range_start_label_qc_f')
                                                        ->label('Start Label')
                                                        ->options(function (callable $get) {
                                                            $orderType = $get('order_type');
                                                            $orderId = $get('order_id');
                                                            return \App\Models\CuttingLabel::where('order_type', $orderType)
                                                                ->where('order_id', $orderId)
                                                                ->orderBy('label')
                                                                ->get()
                                                                ->mapWithKeys(function ($label) {
                                                                    return [
                                                                        $label->id => "{$label->id} | {$label->barcode_id}"
                                                                    ];
                                                                });
                                                        })
                                                        ->reactive()
                                                        ->searchable(),

                                                    Select::make('range_end_label_qc_f')
                                                        ->label('End Label')
                                                        ->options(function (callable $get) {
                                                            $orderType = $get('order_type');
                                                            $orderId = $get('order_id');
                                                            return \App\Models\CuttingLabel::where('order_type', $orderType)
                                                                ->where('order_id', $orderId)
                                                                ->orderBy('label')
                                                                ->get()
                                                                ->mapWithKeys(function ($label) {
                                                                    return [
                                                                        $label->id => "{$label->id} | {$label->barcode_id}"
                                                                    ];
                                                                });
                                                        })
                                                        ->reactive()
                                                        ->searchable(),

                                                    Actions::make([
                                                        Action::make('apply_range_qc_f')
                                                            ->label('Apply Label Range')
                                                            ->action(function (callable $get, callable $set) {
                                                                $orderType = $get('order_type');
                                                                $orderId = $get('order_id');
                                                                $startId = $get('range_start_label_qc_f');
                                                                $endId = $get('range_end_label_qc_f');
                                                                $passedLabels = $get('selected_labels_qc_p') ?? [];

                                                                if (!$orderType || !$orderId || !$startId || !$endId) {
                                                                    return;
                                                                }

                                                                $labels = \App\Models\CuttingLabel::where('order_type', $orderType)
                                                                    ->where('order_id', $orderId)
                                                                    ->orderBy('label')
                                                                    ->get();

                                                                $inRange = false;
                                                                $rangeIds = [];
                                                                foreach ($labels as $label) {
                                                                    if ($label->id == $startId || $label->id == $endId) {
                                                                        $rangeIds[] = $label->id;
                                                                        if ($startId !== $endId) {
                                                                            $inRange = !$inRange;
                                                                        } else {
                                                                            break;
                                                                        }
                                                                    } elseif ($inRange) {
                                                                        $rangeIds[] = $label->id;
                                                                    }
                                                                }
                                                                $rangeIds = array_diff($rangeIds, $passedLabels);
                                                                $existing = $get('selected_labels_qc_f') ?? [];
                                                                $set('selected_labels_qc_f', array_unique([...$existing, ...$rangeIds]));
                                                            })
                                                            ->color('primary'),
                                                    ])
                                                ]),

                                            Grid::make(1)
                                                ->schema([
                                                    CheckboxList::make('selected_labels_qc_f')
                                                        ->label('QC Failed Labels')
                                                        ->options(function (callable $get) {
                                                            $orderType = $get('order_type');
                                                            $orderId = $get('order_id');
                                                            $passedLabels = $get('selected_labels_qc_p') ?? [];
                                                            return \App\Models\CuttingLabel::where('order_type', $orderType)
                                                                ->where('order_id', $orderId)
                                                                ->get()
                                                                ->mapWithKeys(function ($label) use ($passedLabels) {
                                                                    if (in_array($label->id, $passedLabels)) {
                                                                        return [];
                                                                    }
                                                                    return [
                                                                        $label->id => "ID: {$label->id} | Barcode: {$label->barcode_id}"
                                                                    ];
                                                                });
                                                        })
                                                        ->columns(3)
                                                        ->reactive()
                                                        ->searchable()
                                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                            $set('selected_labels_qc_f', is_array($state) ? $state : []);
                                                        })
                                                        ->dehydrated(),
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

                                                Hidden::make('passed_items_count')
                                                    ->default(function (callable $get) {
                                                        $passedLabels = $get('selected_labels_qc_p') ?? [];
                                                        return count($passedLabels);
                                                    })
                                                    ->live(),

                                                Hidden::make('failed_items_count')
                                                    ->default(function (callable $get) {
                                                        $failedLabels = $get('selected_labels_qc_f') ?? [];
                                                        return count($failedLabels);
                                                    })
                                                    ->live(),

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
                                                                    ->mapWithKeys(function ($station) {
                                                                        return [
                                                                            $station->id => 'ID:' . $station->id . ' | Name:' . $station->name,
                                                                        ];
                                                                    })
                                                                    ->toArray();
                                                            })
                                                            ->visible(fn (callable $get) => $get('failed_item_action') === 'cutting_section')
                                                            ->required(fn (callable $get) => $get('failed_item_action') === 'cutting_section')
                                                            ->reactive(),

                                                    
                                                        Select::make('sawing_operation_id')
                                                            ->label('Sawing Operation')
                                                            ->searchable()
                                                            ->options(function () {
                                                                return \App\Models\AssignDailyOperationLine::with('assignDailyOperation')
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
                        ])
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Record ID')->searchable()->sortable()->formatStateUsing(fn (?string $state): string => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('order_type')->label('Order Type'),
                TextColumn::make('order_id')
                    ->label('Order ID')
                    ->sortable()
                    ->formatStateUsing(fn (?string $state): string => str_pad($state, 5, '0', STR_PAD_LEFT)),
                TextColumn::make('inspected_date')->label('Inspected Date')->date(),
                TextColumn::make('qc_officer_id')->label('QC officer')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')->label('Status'),
                ...(
                Auth::user()->can('view audit columns')
                    ? [
                        TextColumn::make('created_by')->label('Created By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('updated_by')->label('Updated By')->toggleable(isToggledHiddenByDefault: true)->sortable(),
                        TextColumn::make('created_at')->label('Created At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                        TextColumn::make('updated_at')->label('Updated At')->toggleable(isToggledHiddenByDefault: true)->dateTime()->sortable(),
                    ]
                    : []
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_type')
                    ->label('Order Type')
                    ->options([
                        'customer_order' => 'Customer Order',
                        'sample_order' => 'Sample Order',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'reported' => 'Reported',
                        'recorded' => 'Recorded',
                    ]),
        
                Tables\Filters\Filter::make('inspected_date')
                    ->label('Inspected Date')
                    ->form([
                        DatePicker::make('inspected_date')
                            ->label('Select Inspected Date')
                            ->maxDate(now()->toDateString())
                            ->closeOnDateSelection(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['inspected_date'] ?? null,
                            fn ($q, $date) => $q->whereDate('inspected_date', $date)
                        );
                    }),
            ])
            ->actions([
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinalProductQCS::route('/'),
            'create' => Pages\CreateFinalProductQC::route('/create'),
        ];
    }
}