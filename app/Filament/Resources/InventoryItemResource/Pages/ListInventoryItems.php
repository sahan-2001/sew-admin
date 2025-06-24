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
                ]),
            Actions\CreateAction::make(),
        ];
    }
}

class CustomInventoryImport extends EnhancedDefaultImport
{
    protected function beforeCollection(Collection $collection): void
    {
        // Validate required headers
        $requiredHeaders = ['name', 'uom'];
        $this->validateHeaders($requiredHeaders, $collection);
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
