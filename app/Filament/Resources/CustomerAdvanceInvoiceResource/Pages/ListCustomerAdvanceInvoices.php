<?php

namespace App\Filament\Resources\CustomerAdvanceInvoiceResource\Pages;

use App\Filament\Resources\CustomerAdvanceInvoiceResource;
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

class ListCustomerAdvanceInvoices extends ListRecords
{
    protected static string $resource = CustomerAdvanceInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Advance Invoices')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('advance-invoices-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('Invoice ID'),
                            Column::make('order_type')->heading('Order Type'),
                            Column::make('order_id')->heading('Order ID'),
                            Column::make('grand_total')->heading('Grand Total'),
                            Column::make('payment_type')->heading('Payment Type'),
                            Column::make('fix_payment_amount')
                                ->heading('Fixed Payment')
                                ->getStateUsing(fn ($record) => number_format($record->fix_payment_amount, 2)),
                            Column::make('payment_percentage')
                                ->heading('Payment %')
                                ->getStateUsing(fn ($record) => $record->payment_percentage . '%'),
                            Column::make('percent_calculated_payment')
                                ->heading('Calculated Payment')
                                ->getStateUsing(fn ($record) => number_format($record->percent_calculated_payment, 2)),
                            Column::make('received_amount')
                                ->heading('Received Amount')
                                ->getStateUsing(fn ($record) => number_format($record->received_amount, 2)),
                            Column::make('paid_date')
                                ->heading('Paid Date')
                                ->getStateUsing(fn ($record) => optional($record->paid_date)->format('Y-m-d')),
                            Column::make('paid_via')->heading('Paid Via'),
                            Column::make('cus_invoice_number')->heading('Invoice Number'),
                            Column::make('created_at')
                                ->heading('Created At')
                                ->getStateUsing(fn ($record) => $record->created_at->format('Y-m-d H:i:s')),
                            Column::make('updated_at')
                                ->heading('Updated At')
                                ->getStateUsing(fn ($record) => $record->updated_at->format('Y-m-d H:i:s')),
                        ])
                ])
                ->modalHeading('Export Advance Invoices')
                ->modalDescription('Export advance invoice records with selected fields.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('cus_adv_invoice.export')),

            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create cus_adv_invoices')),

        ];
    }
}
