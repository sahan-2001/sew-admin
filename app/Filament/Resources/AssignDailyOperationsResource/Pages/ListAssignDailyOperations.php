<?php

namespace App\Filament\Resources\AssignDailyOperationsResource\Pages;

use App\Filament\Resources\AssignDailyOperationsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use EightyNine\ExcelImport\EnhancedDefaultImport;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

class ListAssignDailyOperations extends ListRecords
{
    protected static string $resource = AssignDailyOperationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Daily Operations')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('assign-daily-operations-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('order_type')->heading('Order Type'),
                            Column::make('order_id')->heading('Order ID'),
                            Column::make('operation_date')->heading('Operation Date')
                                ->getStateUsing(fn($record) => optional($record->operation_date)->format('Y-m-d')),
                            Column::make('created_at')->heading('Created At')
                                ->getStateUsing(fn($record) => $record->created_at->format('Y-m-d H:i:s')),
                            Column::make('updated_at')->heading('Updated At')
                                ->getStateUsing(fn($record) => $record->updated_at->format('Y-m-d H:i:s')),
                        ])
                        ->modifyQueryUsing(fn($query) => $query->with('lines'))
                ])
                ->modalHeading('Export Assign Daily Operations')
                ->modalDescription('Export daily operation assignments with relevant details.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('assign daily operation.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create assign daily operations')),
        ];
    }
}
