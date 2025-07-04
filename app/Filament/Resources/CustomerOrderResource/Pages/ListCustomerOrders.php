<?php

namespace App\Filament\Resources\CustomerOrderResource\Pages;

use App\Filament\Resources\CustomerOrderResource;
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
use Filament\Pages\Actions\Action;

class ListCustomerOrders extends ListRecords
{
    protected static string $resource = CustomerOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Customer Orders')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('customer-orders-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('order_id')->heading('Order ID'),
                            Column::make('name')->heading('Order Name'),
                            Column::make('customer.name')->heading('Customer Name')->getStateUsing(
                                fn ($record) => $record->customer?->name
                            ),
                            Column::make('wanted_delivery_date')->heading('Delivery Date'),
                            Column::make('grand_total')->heading('Grand Total')->getStateUsing(
                                fn ($record) => number_format($record->grand_total, 2)
                            ),
                            Column::make('special_notes')->heading('Notes'),
                            Column::make('status')->heading('Status'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => $record->created_at->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => $record->updated_at->format('Y-m-d H:i:s')
                            ),
                        ])
                        ->modifyQueryUsing(fn ($query) => $query->with('customer')) 
                ])
                ->modalHeading('Export Customer Orders')
                ->modalDescription('Export customer orders with summary information.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()->can('customer_orders.export')),
            
            Action::make('goToCustomPage')
                ->label('Convert from a Sample Order')
                ->icon('heroicon-m-arrow-top-right-on-square') 
                ->url(CustomPage::getUrl())
                ->color('success'), 

            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()->can('create customer orders')),

        ];
    }
}