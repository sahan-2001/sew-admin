<?php


namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade as PDF;
use App\Exports\ActivitiesExport;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard';
    protected static ?string $navigationLabel = 'Activity Logs';
    protected static ?string $navigationGroup = 'System';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('log_name')->label('Log Name')->disabled(),
                Forms\Components\TextInput::make('description')->label('Description')->disabled(),
                Forms\Components\TextInput::make('causer.name')->label('Caused By')->disabled(),
                Forms\Components\DateTimePicker::make('created_at')->label('Created At')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('log_name')->label('Log Name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('description')->label('Description')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('causer.name')->label('Caused By')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created At')->sortable()->dateTime(),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('Created From'),
                        Forms\Components\DatePicker::make('created_until')->label('Created Until'),
                        Forms\Components\Checkbox::make('today')
                            ->label('Today')
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('created_from', $state ? now()->startOfDay() : null))
                            ->afterStateUpdated(fn ($state, callable $set) => $set('created_until', $state ? now()->endOfDay() : null)),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export')
                    ->form([
                        Forms\Components\Select::make('export_type')
                            ->label('Export Type')
                            ->options([
                                'pdf' => 'PDF',
                                'excel' => 'Excel',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date')
                            ->required()
                            ->visible(fn ($get) => !$get('today')),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date')
                            ->required()
                            ->visible(fn ($get) => !$get('today')),
                        Forms\Components\Checkbox::make('today')
                            ->label('Today')
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('from_date', $state ? now()->startOfDay() : null))
                            ->afterStateUpdated(fn ($state, callable $set) => $set('to_date', $state ? now()->endOfDay() : null)),
                        Forms\Components\Select::make('user_scope')
                            ->label('User Scope')
                            ->options([
                                'self' => 'Self Activity',
                                'all' => 'All User Activity',
                            ])
                            ->visible(fn () => auth()->user()->can('view other users activity logs'))
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        if ($data['today']) {
                            $data['from_date'] = now()->startOfDay();
                            $data['to_date'] = now()->endOfDay();
                        }

                        if (!isset($data['user_scope'])) {
                            $data['user_scope'] = 'self';
                        }

                        $query = Activity::query()
                            ->whereBetween('created_at', [$data['from_date'], $data['to_date']]);

                        if ($data['user_scope'] === 'self' || !auth()->user()->can('view other users activity logs')) {
                            $query->where('causer_id', auth()->id());
                        }

                        $activities = $query->get();

                        if ($data['export_type'] === 'pdf') {
                            $pdf = PDF::loadView('exports.activities', ['activities' => $activities]);
                            return $pdf->download('activities.pdf');
                        } elseif ($data['export_type'] === 'excel') {
                            return Excel::download(new ActivitiesExport($activities), 'activities.xlsx');
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}