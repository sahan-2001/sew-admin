<?php

namespace App\Filament\Resources\SampleOrderResource\Pages;

use App\Filament\Resources\SampleOrderResource;
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

class ListSampleOrders extends ListRecords
{
    protected static string $resource = SampleOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Sample Orders')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('sample-orders-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('order_id')->heading('Order ID'),
                            Column::make('name')->heading('Order Name'),
                            Column::make('customer.name')->heading('Customer')->getStateUsing(
                                fn ($record) => $record->customer?->name ?? ''
                            ),
                            Column::make('wanted_delivery_date')->heading('Wanted Delivery Date')->getStateUsing(
                                fn ($record) => optional($record->wanted_delivery_date)->format('Y-m-d')
                            ),
                            Column::make('special_notes')->heading('Special Notes'),
                            Column::make('status')->heading('Status'),
                            Column::make('grand_total')->heading('Grand Total')->getStateUsing(
                                fn ($record) => number_format($record->grand_total, 2)
                            ),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => optional($record->created_at)->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => optional($record->updated_at)->format('Y-m-d H:i:s')
                            ),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'addedBy']))
                ])
                ->modalHeading('Export Sample Orders')
                ->modalDescription('Export sample orders with related customer and creator info.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('sample orders.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create sample orders')),
        ];
    }

}
