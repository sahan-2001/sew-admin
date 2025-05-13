<?php

namespace App\Filament\Resources\MaterialQCResource\Pages;

use App\Filament\Resources\MaterialQCResource;
use App\Models\MaterialQC;
use App\Models\RegisterArrivalItem;
use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateMaterialQC extends CreateRecord
{
    protected static string $resource = MaterialQCResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
{
    // Extract items from form data
    $items = $data['items'] ?? [];

    // Remove items from main data to avoid saving directly
    unset($data['items']);

    foreach ($items as $item) {
        MaterialQC::create([
            'purchase_order_id' => $data['purchase_order_id'],
            'item_id' => $item['item_id'],
            'inspected_quantity' => $item['inspected_quantity'],
            'approved_qty' => $item['approved_qty'],
            'returned_qty' => $item['returned_qty'],
            'scrapped_qty' => $item['scrapped_qty'],
'cost_of_item' => $item['cost_of_item'] ?? 0,
            'store_location_id' => $item['store_location_id'],
            'inspected_by' => $item['inspected_by'],
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
    }

    // Return an empty array since we are manually creating records
    return [];
}
}