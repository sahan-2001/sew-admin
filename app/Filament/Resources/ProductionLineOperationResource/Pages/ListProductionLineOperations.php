<?php

namespace App\Filament\Resources\ProductionLineOperationResource\Pages;

use App\Filament\Resources\ProductionLineOperationResource;
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

class ListProductionLineOperations extends ListRecords
{
    protected static string $resource = ProductionLineOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Workstations')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('workstations-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('production_line_id')->heading('production Line ID'),
                            Column::make('productionLine.name')->heading('Production Line')->getStateUsing(
                                fn ($record) => $record->productionLine?->name ?? ''
                            ),
                            Column::make('name')->heading('Workstation Name'),
                            Column::make('description')->heading('Description'),
                            Column::make('status')->heading('Status'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => $record->created_at?->format('Y-m-d H:i:s') ?? ''
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => $record->updated_at?->format('Y-m-d H:i:s') ?? ''
                            ),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->with('productionLine'))
                ])
                ->modalHeading('Export Workstations')
                ->modalDescription('Export Workstation records along with their Production Lines.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('workstations.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create workstations')),
        ];
    }
}
