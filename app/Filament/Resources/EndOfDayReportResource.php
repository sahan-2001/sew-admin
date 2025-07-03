<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EndOfDayReportResource\Pages;
use App\Filament\Resources\EndOfDayReportResource\RelationManagers;
use App\Models\EndOfDayReport;
use App\Models\EnterPerformanceRecord;
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'End of Day Report';
    protected static ?string $navigationGroup = 'Daily Production';

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
                                                    $records = \App\Models\EnterPerformanceRecord::whereDate('operation_date', $state)
                                                        ->where('status', 'pending') 
                                                        ->get();

                                                    \Log::info('Matching Pending Records Full Data:', $records->toArray());

                                                    if ($records->isEmpty()) {
                                                        $set('matching_records_full', []);
                                                        $set('matching_records_count', 0);
                                                        $set('matching_record_ids', null);

                                                        \Filament\Notifications\Notification::make()
                                                            ->title('No Pending Records Found')
                                                            ->body('There are no pending performance records for the selected date or records are not in "pending" status')
                                                            ->warning()
                                                            ->persistent()
                                                            ->duration(5000)
                                                            ->send();
                                                    } else {
                                                        $set('matching_records_full', $records->toArray());
                                                        $set('matching_records_count', $records->count());

                                                        $details = $records->map(function ($record) {
                                                            return "Performance Record ID: {$record->id} (Assigned Operation ID: {$record->assign_daily_operation_id})";
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
                                        Forms\Components\Repeater::make('matching_records_full')
                                            ->label('Performance Records')
                                            ->schema([
                                                Forms\Components\TextInput::make('id')
                                                    ->label('Record ID')
                                                    ->disabled()
                                                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                                                Forms\Components\TextInput::make('assign_daily_operation_id')
                                                    ->label('Assigned Operation ID')
                                                    ->disabled()
                                                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                                                Forms\Components\TextInput::make('assign_daily_operation_line_id')
                                                    ->label('Assigned Operation Line ID')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                                                Forms\Components\TimePicker::make('operated_time_from')
                                                    ->label('Operated From')
                                                    ->disabled(),
                                                Forms\Components\TimePicker::make('operated_time_to')
                                                    ->label('Operated To')
                                                    ->disabled(),
                                                Forms\Components\TextInput::make('created_by')
                                                    ->label('Created By (Employee ID)')
                                                    ->disabled(),
                                            ])
                                            ->columns(3)
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
                Tables\Columns\TextColumn::make('id')->label('Reported ID')->searchable()->formatStateUsing(fn ($state) => str_pad($state, 5, '0', STR_PAD_LEFT)),
                Tables\Columns\TextColumn::make('operated_date')->label('Operation Date')->date(),
                Tables\Columns\TextColumn::make('recorded_operations_count')->label('No of Reported Operations'),
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
                        }

                        if (!empty($enterPerformanceRecordIds)) {
                            \App\Models\EnterPerformanceRecord::whereIn('id', $enterPerformanceRecordIds)
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
