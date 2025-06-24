<?php

namespace App\Filament\Resources\InventoryLocationResource\Pages;

use App\Filament\Resources\InventoryLocationResource;
use App\Models\InventoryLocation;
use App\Models\Warehouse;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use EightyNine\ExcelImport\ExcelImportAction;
use EightyNine\ExcelImport\EnhancedDefaultImport;
use Illuminate\Support\Collection;

class ListInventoryLocations extends ListRecords
{
    protected static string $resource = InventoryLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->validateUsing([
                    'name' => ['required'],
                    'warehouse_id' => ['required'],
                    'location_type' => ['required'],
                    'capacity' => ['required'],
                    'measurement_unit' => ['required'],
                ])
                ->label('Import Inv Locations')
                ->modalHeading('Upload Excel File')
                ->modalDescription('Required fields: name, warehouse_id, location_type, capacity, measurement_unit')
                ->visible(fn () => auth()->user()?->can('inventory.import')),

            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->can('create inventory locations')),
        ];
    }
}

class CustomInventoryLocationImport extends EnhancedDefaultImport
{
    public string $model = InventoryLocation::class;

    // Predefined units (same as in your InventoryLocationResource Select field)
    protected array $allowedMeasurementUnits = ['liters', 'pallets', 'box', 'cubic_meters'];

    protected function beforeCollection(Collection $collection): void
    {
        $requiredHeaders = [
            'name',
            'warehouse_id',
            'location_type',
            'capacity',
            'measurement_unit',
        ];

        $firstRow = $collection->first();
        if ($firstRow) {
            $headers = array_keys($firstRow->toArray());
            \Log::info('Uploaded InventoryLocation Excel Headers:', $headers);
            $this->validateHeaders($requiredHeaders, $collection);
        }
    }

    protected function beforeCreateRecord(array $data, $row): void
    {
        //  Validate warehouse existence
        if (!Warehouse::find($data['warehouse_id'])) {
            throw new \Exception("Row {$row->getIndex()}: Warehouse ID '{$data['warehouse_id']}' does not exist.");
        }

        //  Validate measurement_unit against predefined values
        if (!in_array($data['measurement_unit'], $this->allowedMeasurementUnits)) {
            throw new \Exception("Row {$row->getIndex()}: Invalid measurement unit '{$data['measurement_unit']}'. Allowed: " . implode(', ', $this->allowedMeasurementUnits));
        }

        //  Default zero capacity if invalid
        if (!is_numeric($data['capacity'])) {
            $data['capacity'] = 0;
        }
    }

    protected function mutateBeforeValidation(array $data): array
    {
        $data['capacity'] = is_numeric($data['capacity']) ? (float) $data['capacity'] : 0;
        return $data;
    }
}
