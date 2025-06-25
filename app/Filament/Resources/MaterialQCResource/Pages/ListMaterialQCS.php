<?php

namespace App\Filament\Resources\MaterialQCResource\Pages;

use App\Filament\Resources\MaterialQCResource;
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

class ListMaterialQCS extends ListRecords
{
    protected static string $resource = MaterialQCResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Material QC Records')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('material-qc-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('purchase_order_id')->heading('Purchase Order ID'),
                            Column::make('item_id')->heading('Item ID'),
                            Column::make('inspected_quantity')->heading('Inspected Quantity'),
                            Column::make('approved_qty')->heading('Approved Quantity'),
                            Column::make('returned_qty')->heading('Returned Quantity'),
                            Column::make('scrapped_qty')->heading('Scrapped Quantity'),
                            Column::make('total_returned')->heading('Total Returned'),
                            Column::make('total_scrap')->heading('Total Scrap'),
                            Column::make('available_to_store')->heading('Available to Store'),
                            Column::make('cost_of_item')->heading('Cost of Item')->getStateUsing(
                                fn ($record) => number_format($record->cost_of_item, 2)
                            ),
                            Column::make('store_location_id')->heading('Store Location ID'),
                            Column::make('inspected_by')->heading('Inspected By'),
                            Column::make('status')->heading('Status'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => $record->created_at->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => $record->updated_at->format('Y-m-d H:i:s')
                            ),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->with([
                            'purchaseOrder',
                            'inventoryItem',
                            'storeLocation',
                            'inspectedBy',
                        ]))
                ])
                ->modalHeading('Export Material QC Records')
                ->modalDescription('Export Material Quality Control records with related data.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('material qc.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create material qc records')),
        ];
    }
}
