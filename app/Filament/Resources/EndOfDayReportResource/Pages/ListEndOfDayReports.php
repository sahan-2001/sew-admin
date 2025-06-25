<?php

namespace App\Filament\Resources\EndOfDayReportResource\Pages;

use App\Filament\Resources\EndOfDayReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use EightyNine\ExcelImport\EnhancedDefaultImport;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

class ListEndOfDayReports extends ListRecords
{
    protected static string $resource = EndOfDayReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export End of Day Reports')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('end-of-day-reports-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('operated_date')->heading('Operated Date')->getStateUsing(
                                fn ($record) => optional($record->operated_date)->format('Y-m-d')
                            ),
                            Column::make('recorded_operations_count')->heading('Operations Count'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => $record->created_at->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => $record->updated_at->format('Y-m-d H:i:s')
                            ),
                        ])
                ])
                ->modalHeading('Export End of Day Reports')
                ->modalDescription('Download a list of end-of-day reports as Excel')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('end_of_day_report.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create end of day reports')),
        ];
    }
}
