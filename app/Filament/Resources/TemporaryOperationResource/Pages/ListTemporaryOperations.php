<?php

namespace App\Filament\Resources\TemporaryOperationResource\Pages;

use App\Filament\Resources\TemporaryOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use EightyNine\ExcelImport\ExcelImportAction;
use EightyNine\ExcelImport\EnhancedDefaultImport;
use Illuminate\Support\Collection;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

class ListTemporaryOperations extends ListRecords
{
    protected static string $resource = TemporaryOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Temporary Operations')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('temporary-operations-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('order_type')->heading('Order Type'),
                            Column::make('order_id')->heading('Order ID'),
                            Column::make('customer_id')->heading('Customer ID'),
                            Column::make('wanted_date')->heading('Wanted Date')->getStateUsing(
                                fn ($record) => optional($record->wanted_date)->format('Y-m-d')
                            ),
                            Column::make('description')->heading('Description'),
                            Column::make('productionLine.name')->heading('Production Line')->getStateUsing(
                                fn ($record) => $record->productionLine?->name ?? ''
                            ),
                            Column::make('workstation.name')->heading('Workstation')->getStateUsing(
                                fn ($record) => $record->workstation?->name ?? ''
                            ),
                            Column::make('operation_date')->heading('Operation Date')->getStateUsing(
                                fn ($record) => optional($record->operation_date)->format('Y-m-d')
                            ),
                            Column::make('machine_setup_time')->heading('Machine Setup Time (min)'),
                            Column::make('machine_run_time')->heading('Machine Run Time (min)'),
                            Column::make('labor_setup_time')->heading('Labor Setup Time (min)'),
                            Column::make('labor_run_time')->heading('Labor Run Time (min)'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => optional($record->created_at)->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => optional($record->updated_at)->format('Y-m-d H:i:s')
                            ),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->with(['productionLine', 'workstation']))
                ])
                ->modalHeading('Export Temporary Operations')
                ->modalDescription('Download a report of temporary operations including setup and run times.')
                ->modalButton('Export Now')
                ->visible(fn () => auth()->user()?->can('temporary operations.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create temporary operations')),
        ];
    }
}
