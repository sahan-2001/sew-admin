<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EndOfDayReportResource\Pages;
use App\Filament\Resources\EndOfDayReportResource\RelationManagers;
use App\Models\EndOfDayReport;
use App\Models\EnterPerformanceRecord;
use App\Models\TemporaryOperation;
use Filament\Forms;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns;
use Filament\Forms\Components\Tab;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Modal;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;

class EndOfDayReportResource extends Resource
{
    protected static ?string $model = EndOfDayReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'End of Day Report';
    protected static ?string $navigationGroup = 'Daily Production';
    protected static ?int $navigationSort = 15;

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
                                            ->maxDate(today())
                                            ->reactive()
                                            ->disabled(function (string $context) {
                                                if (auth()->user()?->can('select_previous_performance_dates')) {
                                                    return false;
                                                }
                                                return $context !== 'create'; 
                                            })
                                            ->afterStateUpdated(function (callable $set, $state) {
                                                if ($state) {
                                                    // Fetch performance records
                                                    $performanceRecords = EnterPerformanceRecord::whereDate('operation_date', $state)
                                                        ->where('status', 'pending')
                                                        ->get();

                                                    // Fetch temporary operations
                                                    $temporaryOperations = TemporaryOperation::whereDate('operation_date', $state)
                                                        ->where('status', 'created')
                                                        ->get();

                                                    \Log::info('Matching Records:', [
                                                        'performance' => $performanceRecords->toArray(),
                                                        'temporary' => $temporaryOperations->toArray()
                                                    ]);

                                                    if ($performanceRecords->isEmpty() && $temporaryOperations->isEmpty()) {
                                                        $set('matching_records_full', []);
                                                        $set('matching_records_count', 0);
                                                        $set('matching_record_ids', null);

                                                        Notification::make()
                                                            ->title('No Pending Records Found')
                                                            ->body('No pending performance records or temporary operations found for the selected date')
                                                            ->warning()
                                                            ->persistent()
                                                            ->duration(5000)
                                                            ->send();
                                                    } else {
                                                        $combinedRecords = $performanceRecords->map(function ($record) {
                                                            return [
                                                                'type' => 'performance',
                                                                'data' => $record->toArray()
                                                            ];
                                                        })->concat($temporaryOperations->map(function ($operation) {
                                                            return [
                                                                'type' => 'temporary',
                                                                'data' => $operation->toArray()
                                                            ];
                                                        }));

                                                        $set('matching_records_full', $combinedRecords->toArray());
                                                        $set('matching_records_count', $combinedRecords->count());

                                                        $details = $combinedRecords->map(function ($record) {
                                                            if ($record['type'] === 'performance') {
                                                                return "Performance Record ID: {$record['data']['id']} (Assigned Operation ID: {$record['data']['assign_daily_operation_id']})";
                                                            } else {
                                                                return "Temporary Operation ID: {$record['data']['id']} (Order ID: {$record['data']['order_id']})";
                                                            }
                                                        })->implode("\n");
                                                        
                                                        $set('matching_record_ids', $details);
                                                    }
                                                } else {
                                                    $set('matching_records_full', []);
                                                    $set('matching_records_count', 0);
                                                    $set('matching_record_ids', null);
                                                }
                                            }),

                                        Textarea::make('matching_record_ids')
                                            ->label('Recorded operations')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->rows(5)
                                            ->columnSpan(2),
                                            
                                        TextInput::make('matching_records_count')
                                            ->label('Number of Recorded operations')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->numeric(),
                                    ]),
                            ]),
                        Tabs\Tab::make('Recorded Operations')
                            ->schema([
                                Section::make()
                                    ->columns(1)
                                    ->schema([
                                        Repeater::make('matching_records_full')
                                            ->label('Performance Records & Temporary Operations')
                                            ->schema([
                                                Select::make('type')
                                                    ->label('Record Type')
                                                    ->options([
                                                        'performance' => 'Performance Record',
                                                        'temporary' => 'Temporary Operation',
                                                    ])
                                                    ->disabled(),
                                                
                                                Grid::make(3)
                                                    ->schema(function (Get $get) {
                                                        $type = $get('type');
                                                        
                                                        if ($type === 'performance') {
                                                            return [
                                                                TextInput::make('data.id')
                                                                    ->label('Record ID')
                                                                    ->disabled()
                                                                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                                                                TextInput::make('data.assign_daily_operation_id')
                                                                    ->label('Assigned Operation ID')
                                                                    ->disabled()
                                                                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                                                                TextInput::make('data.assign_daily_operation_line_id')
                                                                    ->label('Assigned Operation Line ID')
                                                                    ->disabled()
                                                                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                                                                TimePicker::make('data.operated_time_from')
                                                                    ->label('Operated From')
                                                                    ->disabled(),
                                                                TimePicker::make('data.operated_time_to')
                                                                    ->label('Operated To')
                                                                    ->disabled(),
                                                                TextInput::make('data.created_by')
                                                                    ->label('Created By (Employee ID)')
                                                                    ->disabled(),
                                                            ];
                                                        } else {
                                                            return [
                                                                TextInput::make('data.id')
                                                                    ->label('Operation ID')
                                                                    ->disabled()
                                                                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                                                                TextInput::make('data.order_id')
                                                                    ->label('Order ID')
                                                                    ->disabled(),
                                                                TextInput::make('data.order_type')
                                                                    ->label('Order Type')
                                                                    ->disabled(),
                                                                TextInput::make('data.production_line_id')
                                                                    ->label('Production Line ID')
                                                                    ->disabled(),
                                                                TextInput::make('data.workstation_id')
                                                                    ->label('Workstation ID')
                                                                    ->disabled(),
                                                                TextInput::make('data.status')
                                                                    ->label('Status')
                                                                    ->disabled(),
                                                            ];
                                                        }
                                                    }),
                                            ])
                                            ->columns(1)
                                            ->disableItemDeletion()
                                            ->disableItemCreation()
                                            ->reorderable(false),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Reported ID')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                Tables\Columns\TextColumn::make('operated_date')
                    ->label('Operation Date')
                    ->date(),
                Tables\Columns\TextColumn::make('recorded_operations_count')
                    ->label('No of Reported Operations'),
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
                Tables\Filters\Filter::make('created_at')
                    ->label('Created At')
                    ->form([
                        Forms\Components\DatePicker::make('created_at')
                            ->label('Select Date')
                            ->placeholder('Created date')
                            ->maxDate(now()->toDateString())
                            ->closeOnDateSelection(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['created_at'] ?? null,
                            fn ($q, $date) => $q->whereDate('created_at', $date)
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('downloadPdf')
                    ->label('PDF Report')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (EndOfDayReport $record) => route('end-of-day-reports.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make()
                    ->before(function (EndOfDayReport $record) {
                        $operations = $record->operations()->get();

                        $enterPerformanceRecordIds = [];
                        $assignDailyOperationLineIds = [];
                        $assignDailyOperationIds = [];
                        $temporaryOperationIds = [];

                        foreach ($operations as $operation) {
                            if ($operation->enter_performance_record_id) {
                                $enterPerformanceRecordIds[] = $operation->enter_performance_record_id;
                            }
                            if ($operation->operation_line_id) {
                                $assignDailyOperationLineIds[] = $operation->operation_line_id;
                            }
                            if ($operation->assign_daily_operation_id) {
                                $assignDailyOperationIds[] = $operation->assign_daily_operation_id;
                            }
                            if ($operation->temporary_operation_id) {
                                $temporaryOperationIds[] = $operation->temporary_operation_id;
                            }
                        }

                        if (!empty($enterPerformanceRecordIds)) {
                            EnterPerformanceRecord::whereIn('id', $enterPerformanceRecordIds)
                                ->update(['status' => 'pending']);
                        }

                        if (!empty($assignDailyOperationLineIds)) {
                            \App\Models\AssignDailyOperationLine::whereIn('id', $assignDailyOperationLineIds)
                                ->update(['status' => 'on going']);
                        }

                        if (!empty($assignDailyOperationIds)) {
                            \App\Models\AssignDailyOperation::whereIn('id', array_unique($assignDailyOperationIds))
                                ->update(['status' => 'created']);
                        }

                        if (!empty($temporaryOperationIds)) {
                            TemporaryOperation::whereIn('id', $temporaryOperationIds)
                                ->update(['status' => 'created']);
                        }

                        $record->operations()->delete();
                    })
                    ->visible(fn (EndOfDayReport $record) => $record->status === 'created'),
            ])
            ->defaultSort('id', 'desc')
            ->recordUrl(null);
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
            'index' => Pages\ListEndOfDayReports::route('/'),
            'create' => Pages\CreateEndOfDayReport::route('/create'),
        ];
    }
}