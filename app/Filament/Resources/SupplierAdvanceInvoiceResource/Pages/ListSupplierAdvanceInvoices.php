<?php

namespace App\Filament\Resources\SupplierAdvanceInvoiceResource\Pages;

use App\Filament\Resources\SupplierAdvanceInvoiceResource;
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

class ListSupplierAdvanceInvoices extends ListRecords
{
    protected static string $resource = SupplierAdvanceInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Supplier Advance Invoices')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('supplier-advance-invoices-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('purchaseOrder.order_number')->heading('Purchase Order')->getStateUsing(
                                fn ($record) => $record->purchaseOrder?->order_number ?? ''
                            ),
                            Column::make('status')->heading('Status'),
                            Column::make('grand_total')->heading('Grand Total')->getStateUsing(
                                fn ($record) => number_format($record->grand_total ?? 0, 2)
                            ),
                            Column::make('payment_type')->heading('Payment Type'),
                            Column::make('fix_payment_amount')->heading('Fixed Payment Amount')->getStateUsing(
                                fn ($record) => number_format($record->fix_payment_amount ?? 0, 2)
                            ),
                            Column::make('payment_percentage')->heading('Payment Percentage')->getStateUsing(
                                fn ($record) => $record->payment_percentage ? ($record->payment_percentage * 100) . '%' : ''
                            ),
                            Column::make('percent_calculated_payment')->heading('Percent Calculated Payment')->getStateUsing(
                                fn ($record) => number_format($record->percent_calculated_payment ?? 0, 2)
                            ),
                            Column::make('paid_amount')->heading('Paid Amount')->getStateUsing(
                                fn ($record) => number_format($record->paid_amount ?? 0, 2)
                            ),
                            Column::make('remaining_amount')->heading('Remaining Amount')->getStateUsing(
                                fn ($record) => number_format($record->remaining_amount ?? 0, 2)
                            ),
                            Column::make('paid_date')->heading('Paid Date')->getStateUsing(
                                fn ($record) => optional($record->paid_date)->format('Y-m-d')
                            ),
                            Column::make('paid_via')->heading('Paid Via'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => optional($record->created_at)->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => optional($record->updated_at)->format('Y-m-d H:i:s')
                            ),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->with(['purchaseOrder', 'supplier']))
                ])
                ->modalHeading('Export Supplier Advance Invoices')
                ->modalDescription('Export all Supplier Advance Invoice records with related purchase order and supplier data.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('supplier advance invoices.export')),
                
                Actions\CreateAction::make()
                    ->visible(fn () => auth()->user()?->can('create supplier advance invoices')),
        ];
    }
}
