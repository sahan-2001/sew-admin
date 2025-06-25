<?php

namespace App\Filament\Resources\CuttingStationResource\Pages;

use App\Filament\Resources\CuttingStationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use EightyNine\ExcelImport\EnhancedDefaultImport;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

class ListCuttingStations extends ListRecords
{
    protected static string $resource = CuttingStationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Cutting Stations')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('cutting-stations-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('name')->heading('Name'),
                            Column::make('description')->heading('Description'),
                            Column::make('created_at')
                                ->heading('Created At')
                                ->getStateUsing(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),
                            Column::make('updated_at')
                                ->heading('Updated At')
                                ->getStateUsing(fn ($record) => $record->updated_at->format('Y-m-d H:i:s')),
                        ])
                ])
                ->modalHeading('Export Cutting Stations')
                ->modalDescription('Download a list of cutting stations as Excel')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('cutting_station.export')),
                
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create cutting stations')),
        ];
    }
}
