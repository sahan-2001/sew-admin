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
                Tables\Columns\TextColumn::make('id')->label('Log ID')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('log_name')->label('Log Name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('description')->label('Description')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('causer.name')->label('Caused By')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created At')->sortable()->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
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
                    ->label('Export Activity Logs')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->form([
                        Forms\Components\Checkbox::make('today')
                            ->label('Export Today')
                            ->reactive(),

                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date')
                            ->maxDate(now()->toDateString())
                            ->visible(fn (callable $get) => ! $get('today'))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $toDate = $get('to_date');
                                // If from_date is set after to_date, reset to_date
                                if ($toDate && $state > $toDate) {
                                    $set('to_date', null);
                                }
                            }),

                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date')
                            ->maxDate(now()->toDateString())
                            ->visible(fn (callable $get) => ! $get('today'))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $fromDate = $get('from_date');
                                // If to_date is set before from_date, reset from_date
                                if ($fromDate && $state < $fromDate) {
                                    $set('from_date', null);
                                }
                            }),

                        Forms\Components\TextInput::make('permission_type')
                            ->label('Permission Type')
                            ->disabled()
                            ->default(fn () => auth()->user()->can('view other users activity logs') ? 'View All Users' : 'View Self Only'),
                    ])
                    ->action(function (array $data) {
                        $user = auth()->user();

                        $canViewAll = $user->can('view other users activity logs');
                        $canViewSelf = $user->can('view self activity logs');

                        if (! $canViewAll && ! $canViewSelf) {
                            abort(403, 'You do not have permission to export activity logs.');
                        }

                        // Adjust dates if "today" selected
                        if ($data['today']) {
                            $fromDate = now()->toDateString();
                            $toDate = now()->toDateString();
                        } else {
                            $fromDate = $data['from_date'] ?? null;
                            $toDate = $data['to_date'] ?? null;
                        }

                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\ActivitiesExport(
                                viewAll: $canViewAll,
                                fromDate: $fromDate,
                                toDate: $toDate,
                            ),
                            'activity-logs-' . now()->format('Y-m-d_H-i-s') . '.xlsx'
                        );
                    })
                    ->visible(fn () =>
                        auth()->user()->can('view self activity logs') ||
                        auth()->user()->can('view other users activity logs')
                    )
                    ->modalHeading('Export Activity Logs')
                    ->modalDescription('Select "Today" or specify a date range. The export respects your permission scope.')
                    ->modalButton('Download')
            ]);

    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}