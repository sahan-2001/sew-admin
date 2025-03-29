<?php


namespace App\Filament\Resources\ActivityLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\ActivityLogResource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables;
use Filament\Forms;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade as PDF;
use App\Exports\ActivitiesExport;
use Spatie\Activitylog\Models\Activity;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();

        if (auth()->user()->can('view other users activity logs')) {
            return $query;
        }

        return $query->where('causer_id', auth()->id());
    }

    protected function getTableFilters(): array
    {
        if (auth()->user()->can('view other users activity logs')) {
            return [
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('User')
                    ->options(User::all()->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data) {
                        return $query->where('causer_id', $data['value']);
                    }),
            ];
        }

        return [];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('export')
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
                        ->required(),
                    Forms\Components\DatePicker::make('to_date')
                        ->label('To Date')
                        ->required(),
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
        ];
    }
}