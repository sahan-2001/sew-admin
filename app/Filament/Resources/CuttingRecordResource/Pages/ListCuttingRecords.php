<?php

namespace App\Filament\Resources\CuttingRecordResource\Pages;

use App\Filament\Resources\CuttingRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use EightyNine\ExcelImport\ExcelImportAction;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use EightyNine\ExcelImport\EnhancedDefaultImport;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

class ListCuttingRecords extends ListRecords
{
    protected static string $resource = CuttingRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Cutting Records')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('cutting-records-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('order_type')->heading('Order Type'),
                            Column::make('order_id')->heading('Order ID'),
                            Column::make('cuttingStation.name')->heading('Cutting Station')->getStateUsing(
                                fn ($record) => $record->cuttingStation?->name
                            ),
                            Column::make('releaseMaterial.code')->heading('Release Material')->getStateUsing(
                                fn ($record) => $record->releaseMaterial?->code
                            ),
                            Column::make('operation_date')->heading('Operation Date'),
                            Column::make('operated_time_from')->heading('From Time'),
                            Column::make('operated_time_to')->heading('To Time'),
                            Column::make('notes')->heading('Notes'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => $record->created_at->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => $record->updated_at->format('Y-m-d H:i:s')
                            ),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->with(['cuttingStation', 'releaseMaterial']))
                ])
                ->modalHeading('Export Cutting Records')
                ->modalDescription('Download cutting record details as Excel')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('cutting_record.export')),
                
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create cutting records')),
        ];
    }
}
