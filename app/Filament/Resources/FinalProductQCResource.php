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
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;

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
                                                    $allowedStatuses = ['released', 'material released', 'cut', 'started'];

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
                                                        $set('customer_name', $order->customer->customer_id ?? 'N/A');
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
                                                            $set('customer_id', $order->customer->customer_id ?? 'N/A');
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

                    Tabs\Tab::make('Completed Item Labels')
                    ->schema([
                        Section::make('Existing Labels')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('range_start_label_id')
                                            ->label('Start Label')
                                            ->options(function (callable $get) {
                                                $orderType = $get('order_type');
                                                $orderId = $get('order_id');

                                                return \App\Models\CuttingLabel::where('order_type', $orderType)
                                                    ->where('order_id', $orderId)
                                                    ->orderBy('label')
                                                    ->pluck('label', 'id');
                                            })
                                            ->reactive()
                                            ->searchable()
                                            ->hidden(fn (callable $get) => !($get('order_type') && $get('order_id'))),

                                        Select::make('range_end_label_id')
                                            ->label('End Label')
                                            ->options(function (callable $get) {
                                                $orderType = $get('order_type');
                                                $orderId = $get('order_id');

                                                return \App\Models\CuttingLabel::where('order_type', $orderType)
                                                    ->where('order_id', $orderId)
                                                    ->orderBy('label')
                                                    ->pluck('label', 'id');
                                            })
                                            ->reactive()
                                            ->searchable()
                                            ->hidden(fn (callable $get) => !($get('order_type') && $get('order_id'))),

                                            \Filament\Forms\Components\Actions::make([
                                            \Filament\Forms\Components\Actions\Action::make('apply_range')
                                                ->label('Apply Label Range')
                                                ->action(function ($get, $set) {
                                                    $orderType = $get('order_type');
                                                    $orderId = $get('order_id');
                                                    $startId = $get('range_start_label_id');
                                                    $endId = $get('range_end_label_id');

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

                                                    $existing = $get('selected_label_ids') ?? [];
                                                    $set('selected_label_ids', array_unique([...$existing, ...$rangeIds]));
                                                })
                                                ->color('primary'),
                                        ])
                                        ->hidden(fn (callable $get) => !($get('order_type') && $get('order_id'))),
                                    ]),
                                    
                                Grid::make(2)
                                    ->schema([
                                        Placeholder::make('selected_label_count')
                                            ->label('Selected Labels Count')
                                            ->content(function (callable $get) {
                                                $selected = $get('selected_label_ids') ?? [];
                                                return count($selected) . ' label(s) selected';
                                            })
                                            ->hidden(fn (callable $get) => !($get('order_type') && $get('order_id'))),
                                            
                                        Checkbox::make('select_all_labels')
                                            ->label('Select All Labels')
                                            ->reactive()
                                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                                $orderType = $get('order_type');
                                                $orderId = $get('order_id');

                                                if (!$orderType || !$orderId) {
                                                    return;
                                                }

                                                $labelIds = \App\Models\CuttingLabel::where('order_type', $orderType)
                                                    ->where('order_id', $orderId)
                                                    ->pluck('id')
                                                    ->toArray();

                                                $set('selected_label_ids', $state ? $labelIds : []);
                                            })
                                            ->hidden(fn (callable $get) => !($get('order_type') && $get('order_id'))),
                                        ]),

                                Grid::make(1)
                                    ->schema([
                                        CheckboxList::make('selected_label_ids')
                                            ->label('Cutting Labels')
                                            ->required()
                                            ->options(function (callable $get) {
                                                $orderType = $get('order_type');
                                                $orderId = $get('order_id');

                                                if (!$orderType || !$orderId) {
                                                    return [];
                                                }

                                                return \App\Models\CuttingLabel::where('order_type', $orderType)
                                                    ->where('order_id', $orderId)
                                                    ->pluck('label', 'id');
                                            })
                                            ->columns(3)
                                            ->reactive()
                                            ->searchable()
                                            ->hidden(fn (callable $get) => !($get('order_type') && $get('order_id')))
                                            ->afterStateHydrated(function ($component, $record) {
                                                if ($record) {
                                                    $component->state($record->labels->pluck('id')->toArray());
                                                }
                                            })
                                            ->dehydrated(false)
                                            ->saveRelationshipsUsing(function ($record, $state) {
                                                $record->labels()->syncWithPivotValues($state ?? [], [], false);
                                            }),
                                    ])
                            ])
                            ->collapsible()
                    ])
                ])
                ->columnSpanFull(),
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