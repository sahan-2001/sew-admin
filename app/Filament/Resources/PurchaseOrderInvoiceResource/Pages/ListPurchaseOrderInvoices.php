<?php

namespace App\Filament\Resources\PurchaseOrderInvoiceResource\Pages;

use App\Filament\Resources\PurchaseOrderInvoiceResource;
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

class ListPurchaseOrderInvoices extends ListRecords
{
    protected static string $resource = PurchaseOrderInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Purchase Order Invoices')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('purchase-order-invoices-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('purchase_order_id')->heading('Purchase Order ID'),
                            Column::make('provider_type')->heading('Provider Type'),
                            Column::make('provider_id')->heading('Provider ID'),
                            Column::make('status')->heading('Status'),
                            Column::make('grand_total')->heading('Grand Total')->getStateUsing(
                                fn ($record) => number_format($record->grand_total, 2)
                            ),
                            Column::make('adv_paid')->heading('Advance Paid')->getStateUsing(
                                fn ($record) => number_format($record->adv_paid ?? 0, 2)
                            ),
                            Column::make('additional_cost')->heading('Additional Cost')->getStateUsing(
                                fn ($record) => number_format($record->additional_cost ?? 0, 2)
                            ),
                            Column::make('discount')->heading('Discount')->getStateUsing(
                                fn ($record) => number_format($record->discount ?? 0, 2)
                            ),
                            Column::make('due_payment')->heading('Due Payment')->getStateUsing(
                                fn ($record) => number_format($record->due_payment ?? 0, 2)
                            ),
                            Column::make('due_payment_for_now')->heading('Due Payment For Now')->getStateUsing(
                                fn ($record) => number_format($record->due_payment_for_now ?? 0, 2)
                            ),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => optional($record->created_at)->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => optional($record->updated_at)->format('Y-m-d H:i:s')
                            ),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->with('purchaseOrder'))
                ])
                ->modalHeading('Export Purchase Order Invoices')
                ->modalDescription('Export purchase order invoice records including order details, totals, and status.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('purchase_order_invoices.export')),


            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create purchase order invoices')),
        ];
    }
}
