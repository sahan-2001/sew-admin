<?php

namespace App\Filament\Resources\InventoryItemResource\Pages;

use App\Filament\Resources\InventoryItemResource;
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

class ListInventoryItems extends ListRecords
{
    protected static string $resource = InventoryItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->validateUsing([
                        'name' => ['required'],
                        'uom' => ['required'],
                    ])
                ->label('Import Inv Items')
                ->modalHeading('Upload Excel File')
                ->visible(fn () => auth()->user()?->can('inventory.import'))
                ->modalDescription('Required fields: name, uom'),

            ExportAction::make()
                ->label('Export Inventory Items')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('inventory-items-' . now()->format('Y-m-d-H-i-s'))
                        ->withColumns([
                            Column::make('id')->heading('ID'),
                            Column::make('item_code')->heading('Item Code'),
                            Column::make('name')->heading('Name'),
                            Column::make('category')->heading('Category'),
                            Column::make('special_note')->heading('Special Note'),
                            Column::make('uom')->heading('Unit of Measure'),
                            Column::make('available_quantity')->heading('Available Quantity'),
                            Column::make('created_at')->heading('Created At')->getStateUsing(
                                fn ($record) => $record->created_at->format('Y-m-d H:i:s')
                            ),
                            Column::make('updated_at')->heading('Updated At')->getStateUsing(
                                fn ($record) => $record->updated_at->format('Y-m-d H:i:s')
                            ),
                        ])
                ])
                ->modalHeading('Export Inventory Items')
                ->modalDescription('Download inventory item details as Excel')
                ->modalButton('Start Export')
                ->visible(fn () => auth()->user()?->can('inventory.export')),

            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create inventory items')),
        ];
    }
}

class CustomInventoryImport extends EnhancedDefaultImport
{
    protected function beforeCollection(Collection $collection): void
    {
        $firstRow = $collection->first(); 
        if ($firstRow) {
            $headers = array_keys($firstRow->toArray());
            \Log::info('Uploaded Excel Headers:', $headers);
            $requiredHeaders = ['name', 'uom'];
            $this->validateHeaders($requiredHeaders, $collection);
        }
    }

    protected function beforeCreateRecord(array $data, $row): void
    {
        // Handle category - create if doesn't exist
        if (empty($data['category'])) {
            $data['category'] = 'uncategorized';
        }

        // Ensure the category exists in the Category model
        $category = Category::firstOrCreate(
            ['name' => $data['category']],
            ['created_by' => auth()->id() ?? 1] 
        );

        $data['category'] = $category->name;

        // Set default quantity
        if (!isset($data['available_quantity'])) {
            $data['available_quantity'] = 0;
        }
    }

    protected function mutateBeforeValidation(array $data): array
    {
        // Ensure numeric values for quantity
        if (isset($data['available_quantity'])) {
            $data['available_quantity'] = is_numeric($data['available_quantity']) 
                ? $data['available_quantity'] 
                : 0;
        }

        return $data;
    }
}
