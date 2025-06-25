<?php

namespace App\Filament\Resources\StockOverviewResource\Pages;

use App\Filament\Resources\StockOverviewResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use EightyNine\ExcelImport\ExcelImportAction;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use EightyNine\ExcelImport\EnhancedDefaultImport;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

class ListStockOverview extends ListRecords
{
    protected static string $resource = StockOverviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->validateUsing([
                    'item_id' => ['required', 'exists:inventory_items,id'],
                    'quantity' => ['required', 'numeric', 'min:1'],
                    'cost' => ['required', 'numeric', 'min:0'],
                    'location_id' => ['required', 'exists:inventory_locations,id'],
                ])
                ->label('Import Stock')
                ->modalHeading('Upload Stock Excel File')
                ->modalDescription('Required fields: item_id, quantity, cost, location_id')
                ->visible(fn () => auth()->user()?->can('stocks.import')),
                                
            ExportAction::make()
                ->label('Export Stock Records')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('stock-records-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('item_id')->heading('Item ID'),
                            Column::make('item.name')->heading('Item Name')->getStateUsing(
                                fn ($record) => $record->item?->name ?? ''
                            ),
                            Column::make('quantity')->heading('Quantity'),
                            Column::make('cost')->heading('Cost')->getStateUsing(
                                fn ($record) => number_format($record->cost, 2)
                            ),
                            Column::make('location.name')->heading('Location')->getStateUsing(
                                fn ($record) => $record->location?->name ?? ''
                            ),
                            Column::make('purchaseOrder.order_number')->heading('Purchase Order')->getStateUsing(
                                fn ($record) => $record->purchaseOrder?->order_number ?? ''
                            ),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => optional($record->created_at)->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => optional($record->updated_at)->format('Y-m-d H:i:s')
                            ),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->with(['item', 'location', 'purchaseOrder']))
                ])
                ->modalHeading('Export Stock Records')
                ->modalDescription('Export stock quantities with related item, location, and purchase order details.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('stock.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create emergency stocks')),
        ];
    }
}
