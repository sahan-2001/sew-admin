<?php

namespace App\Filament\Resources\EnterPerformanceRecordResource\Pages;

use App\Filament\Resources\EnterPerformanceRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use EightyNine\ExcelImport\EnhancedDefaultImport;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

class ListEnterPerformanceRecords extends ListRecords
{
    protected static string $resource = EnterPerformanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Performance Records')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('performance-records-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('assign_daily_operation_id')->heading('Daily Operation ID'),
                            Column::make('assign_daily_operation_line_id')->heading('Operation Line ID'),
                            Column::make('operation_date')->heading('Operation Date')
                                ->getStateUsing(fn ($record) => optional($record->operation_date)->format('Y-m-d')),
                            Column::make('operated_time_from')->heading('Operated From'),
                            Column::make('operated_time_to')->heading('Operated To'),
                            Column::make('actual_machine_setup_time')->heading('Machine Setup Time'),
                            Column::make('actual_machine_run_time')->heading('Machine Run Time'),
                            Column::make('actual_employee_setup_time')->heading('Employee Setup Time'),
                            Column::make('actual_employee_run_time')->heading('Employee Run Time'),
                            Column::make('status')->heading('Status'),
                            Column::make('created_at')->heading('Created At')
                                ->getStateUsing(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),
                            Column::make('updated_at')->heading('Updated At')
                                ->getStateUsing(fn ($record) => $record->updated_at->format('Y-m-d H:i:s')),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query) 
                ])
                ->modalHeading('Export Performance Records')
                ->modalDescription('Export Enter Performance Records with detailed timings and status.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('performance_record.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create performace records')),
        ];
    }
}
