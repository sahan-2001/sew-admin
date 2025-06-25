<?php

namespace App\Filament\Resources\ReleaseMaterialResource\Pages;

use App\Filament\Resources\ReleaseMaterialResource;
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

class ListReleaseMaterials extends ListRecords
{
    protected static string $resource = ReleaseMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Release Materials')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('release-materials-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('order_type')->heading('Order Type'),
                            Column::make('order_id')->heading('Order ID'),
                            Column::make('cutting_station_id')->heading('Cutting Station ID'),
                            Column::make('cuttingStation.name')->heading('Cutting Station')->getStateUsing(
                                fn ($record) => $record->cuttingStation?->name ?? ''
                            ),
                            Column::make('notes')->heading('Notes'),
                            Column::make('status')->heading('Status'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => optional($record->created_at)->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => optional($record->updated_at)->format('Y-m-d H:i:s')
                            ),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->with(['cuttingStation']))
                ])
                ->modalHeading('Export Release Material Records')
                ->modalDescription('Export records of released materials along with related cutting station data.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('release materials.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create release materials')),
        ];
    }
}
