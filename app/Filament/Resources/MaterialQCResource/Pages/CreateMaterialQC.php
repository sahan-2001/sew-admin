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

    protected function handleRecordCreation(array $data): MaterialQC
{
    DB::beginTransaction();

    try {
        $createdRecords = [];
        
        foreach ($data['items'] as $item) {
            if (!$item || !isset($item['item_id'])) {
                throw new \Exception("Item ID is required.");
            }

            $cost = $item['cost_of_item'] ?? 0;

            // Create the MaterialQC record for each item
            $createdRecord = MaterialQC::create([
                'purchase_order_id' => $data['purchase_order_id'],
                'item_id' => $item['item_id'],
                'cost_of_item' => $cost,
                'inspected_quantity' => $item['inspected_quantity'],
                'approved_qty' => $item['approved_qty'],
                'returned_qty' => $item['returned_qty'] ?? 0,
                'scrapped_qty' => $item['scrapped_qty'] ?? 0,
                'add_returned' => $item['add_returned'] ?? 0,
                'add_scrap' => $item['add_scrap'] ?? 0,
                'total__returned' => $item['total_returned'] ?? 0,
                'total_scrap' => $item['total_scrap'] ?? 0,
                'available_to_store' => $item['available_to_store'] ?? 0,
                'store_location_id' => $item['store_location_id'] ?? null,
                'register_arrival_id' => $data['register_arrival_id'], 
                'inspected_by' => $item['inspected_by'],               
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            
            $createdRecords[] = $createdRecord;
            
            // Update the RegisterArrivalItem status
            RegisterArrivalItem::where('item_id', $item['item_id'])
                ->whereHas('registerArrival', function($query) use ($data) {
                    $query->where('purchase_order_id', $data['purchase_order_id']);
                })
                ->update(['status' => 'inspected']);
            
            // Add a new row to the Stock table
            \App\Models\Stock::create([
                'item_id' => $item['item_id'],
                'quantity' => $item['available_to_store'], 
                'cost' => $cost, 
                'location_id' => $item['store_location_id'], 
                'purchase_order_id' => $data['purchase_order_id'], 
            ]);
        }

        DB::commit();
        
        return $createdRecords[0];
        
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

}