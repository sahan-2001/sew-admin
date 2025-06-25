<?php

namespace App\Filament\Resources\NonInventoryItemResource\Pages;

use App\Filament\Resources\NonInventoryItemResource;
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


class ListNonInventoryItems extends ListRecords
{
    protected static string $resource = NonInventoryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Export Non Inventory Items')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('non-inventory-items-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('item_id')->heading('Item ID'),
                            Column::make('name')->heading('Name'),
                            Column::make('category.name')->heading('Category')->getStateUsing(
                                fn($record) => $record->category?->name ?? ''
                            ),
                            Column::make('price')->heading('Price')->getStateUsing(
                                fn($record) => number_format($record->price, 2)
                            ),
                            Column::make('remarks')->heading('Remarks'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn($record) => $record->created_at->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn($record) => $record->updated_at->format('Y-m-d H:i:s')
                            ),
                        ])
                        ->modifyQueryUsing(fn($query) => $query->with(['category']))
                ])
                ->modalHeading('Export Non Inventory Items')
                ->modalDescription('Export all Non Inventory Items with related categories.')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('non inventory item.export')),
    
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create non inventory items')),
        ];
    }
}
