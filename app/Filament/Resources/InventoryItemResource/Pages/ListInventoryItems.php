<?php

namespace App\Filament\Resources\InventoryItemResource\Pages;

use App\Filament\Resources\InventoryItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use EightyNine\ExcelImport\ExcelImportAction;
use EightyNine\ExcelImport\EnhancedDefaultImport;
use Illuminate\Support\Collection;

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
