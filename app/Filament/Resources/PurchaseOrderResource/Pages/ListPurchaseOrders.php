<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions;
use EightyNine\ExcelImport\ExcelImportAction;
use EightyNine\ExcelImport\EnhancedDefaultImport;
use Illuminate\Support\Collection;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Purchase Orders')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('purchase-orders-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('provider_type')->heading('Provider Type'),
                            Column::make('provider_id')->heading('Provider ID'),
                            Column::make('wanted_date')->heading('Wanted Date')->getStateUsing(
                                fn ($record) => optional($record->wanted_date)->format('Y-m-d')
                            ),
                            Column::make('special_note')->heading('Special Note'),
                            Column::make('status')->heading('Status'),
                            Column::make('grand_total')->heading('Grand Total')->getStateUsing(
                                fn ($record) => number_format($record->grand_total, 2)
                            ),
                            Column::make('created_by')->heading('Created By'),
                            Column::make('updated_by')->heading('Updated By'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => $record->created_at?->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => $record->updated_at?->format('Y-m-d H:i:s')
                            ),
                        ])
                ])
                ->modalHeading('Export Purchase Orders')
                ->modalDescription('Export all purchase orders with provider and total details.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('purchase_orders.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create purchase orders')),
        ];
    }
}