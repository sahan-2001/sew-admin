<?php

namespace App\Filament\Resources\ProductionMachineResource\Pages;

use App\Filament\Resources\ProductionMachineResource;
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

class ListProductionMachines extends ListRecords
{
    protected static string $resource = ProductionMachineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->validateUsing([
                    'name' => ['required'],
                    'purchased_date' => ['required'],
                    'start_working_date' => ['required'],
                    'expected_lifetime' => ['required'],
                    'purchased_cost' => ['required'],
                    'total_initial_cost' => ['required'],
                    'depreciation_rate' => ['required'],
                    'depreciation_calculated_from' => ['required'],
                ])
                ->label('Import Production Machines')
                ->modalHeading('Upload Excel File')
                ->modalDescription('Required fields: name, purchased date, start working date, expected lifetime, purchased cost, total initial cost, depreciation_rate, depreciation_calculated_from')
                ->visible(fn () => auth()->user()?->can('production machines.import')),
                
                ExportAction::make()
                ->label('Export Machines')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('production-machines-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('name')->heading('Machine Name'),
                            Column::make('description')->heading('Description'),
                            Column::make('purchased_date')->heading('Purchased Date')->getStateUsing(
                                fn ($record) => optional($record->purchased_date)->format('Y-m-d')
                            ),
                            Column::make('start_working_date')->heading('Start Working Date')->getStateUsing(
                                fn ($record) => optional($record->start_working_date)->format('Y-m-d')
                            ),
                            Column::make('expected_lifetime')->heading('Expected Lifetime (Years)'),
                            Column::make('purchased_cost')->heading('Purchased Cost')->getStateUsing(
                                fn ($record) => number_format($record->purchased_cost, 2)
                            ),
                            Column::make('additional_cost')->heading('Additional Cost')->getStateUsing(
                                fn ($record) => number_format($record->additional_cost ?? 0, 2)
                            ),
                            Column::make('total_initial_cost')->heading('Total Initial Cost')->getStateUsing(
                                fn ($record) => number_format($record->total_initial_cost, 2)
                            ),
                            Column::make('depreciation_rate')->heading('Depreciation Rate')->getStateUsing(
                                fn ($record) => $record->depreciation_rate * 100 . '%'
                            ),
                            Column::make('depreciation_last')->heading('Last Depreciation')->getStateUsing(
                                fn ($record) => number_format($record->depreciation_last ?? 0, 2)
                            ),
                            Column::make('cumulative_depreciation')->heading('Cumulative Depreciation')->getStateUsing(
                                fn ($record) => number_format($record->cumulative_depreciation ?? 0, 2)
                            ),
                            Column::make('net_present_value')->heading('Net Present Value')->getStateUsing(
                                fn ($record) => number_format($record->net_present_value ?? 0, 2)
                            ),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => $record->created_at?->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => $record->updated_at?->format('Y-m-d H:i:s')
                            ),
                        ])
                ])
                ->modalHeading('Export Production Machines')
                ->modalDescription('Export details of all production machines including costs and depreciation.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('production machines.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create production machines')),
        ];
    }
}
