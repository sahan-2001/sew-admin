<?php

namespace App\Filament\Resources\ProductionLineResource\Pages;

use App\Filament\Resources\ProductionLineResource;
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

class ListProductionLines extends ListRecords
{
    protected static string $resource = ProductionLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->validateUsing([
                    'name' => ['required'],
                    'status' => ['required'],
                ])
                ->label('Import Production Lines')
                ->modalHeading('Upload Excel File')
                ->modalDescription('Required fields: name, status(active or inactive)')
                ->visible(fn () => auth()->user()?->can('production lines.import')),
                
            ExportAction::make()
                ->label('Export Production Lines')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('production-lines-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('name')->heading('Production Line Name'),
                            Column::make('description')->heading('Description'),
                            Column::make('status')->heading('Status'),
                            Column::make('workstations_count')->heading('Workstations Count')->getStateUsing(
                                fn ($record) => $record->workstations_count
                            ),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => $record->created_at?->format('Y-m-d H:i:s') ?? ''
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => $record->updated_at?->format('Y-m-d H:i:s') ?? ''
                            ),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->withCount('workstations'))
                ])
                ->modalHeading('Export Production Lines')
                ->modalDescription('Export production line records including workstation count.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('production-lines.export')),
                
                Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create production lines')),
        ];
    }
}
