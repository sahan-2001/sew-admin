<?php

namespace App\Filament\Resources\RegisterArrivalResource\Pages;

use App\Filament\Resources\RegisterArrivalResource;
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

class ListRegisterArrivals extends ListRecords
{
    protected static string $resource = RegisterArrivalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Register Arrivals')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('register-arrivals-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('purchase_order_id')->heading('Purchase Order ID'),
                            Column::make('purchaseOrder.id')->heading('PO Ref')->getStateUsing(
                                fn ($record) => $record->purchaseOrder?->id ?? ''
                            ),
                            Column::make('location.name')->heading('Location')->getStateUsing(
                                fn ($record) => $record->location?->name ?? ''
                            ),
                            Column::make('received_date')->heading('Received Date')->getStateUsing(
                                fn ($record) => optional($record->received_date)->format('Y-m-d')
                            ),
                            Column::make('invoice_number')->heading('Invoice Number'),
                            Column::make('note')->heading('Note'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => optional($record->created_at)->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => optional($record->updated_at)->format('Y-m-d H:i:s')
                            ),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->with(['purchaseOrder', 'location']))
                ])
                ->modalHeading('Export Register Arrival Records')
                ->modalDescription('Export list of Register Arrivals with associated purchase order and location details.')
                ->modalButton('Start Export'),
                #->visible(fn () => auth()->user()?->can('register_arrivals.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create register arrivals')),
        ];
    }
}
