<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Resources\WarehouseResource;
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

class ListWarehouses extends ListRecords
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Warehouses')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('warehouses-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('name')->heading('Warehouse Name'),
                            Column::make('address_line_1')->heading('Address Line 1'),
                            Column::make('address_line_2')->heading('Address Line 2'),
                            Column::make('address_line_3')->heading('Address Line 3'),
                            Column::make('city')->heading('City'),
                            Column::make('note')->heading('Note'),
                            Column::make('capacity_length')->heading('Length'),
                            Column::make('capacity_width')->heading('Width'),
                            Column::make('capacity_height')->heading('Height'),
                            Column::make('measurement_unit')->heading('Measurement Unit'),
                            Column::make('created_by')->heading('Created By'),
                            Column::make('updated_by')->heading('Updated By'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => optional($record->created_at)->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => optional($record->updated_at)->format('Y-m-d H:i:s')
                            ),
                        ])
                ])
                ->modalHeading('Export Warehouses')
                ->modalDescription('Export detailed warehouse information including capacity and location.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('warehouses.export')),
                
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create warehouses')), 
        ];
    }
}
