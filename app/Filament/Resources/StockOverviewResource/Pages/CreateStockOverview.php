<?php

namespace App\Filament\Resources\StockOverviewResource\Pages;

use App\Filament\Resources\StockOverviewResource;
use App\Models\EmergencyStock;
use App\Models\Stock;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateStockOverview extends CreateRecord
{
    protected static string $resource = StockOverviewResource::class;

    protected function handleRecordCreation(array $data): Stock
{
    // First save the Stock entry (required by the resource)
    $stock = Stock::create([
        'item_id' => $data['item_id'],
        'location_id' => $data['location_id'],
        'quantity' => $data['quantity'],
        'cost' => $data['cost'],
        'purchase_order_id' => null,
    ]);

    // After Stock is saved, save EmergencyStock
    EmergencyStock::create([
        'item_id' => $data['item_id'],
        'location_id' => $data['location_id'],
        'quantity' => $data['quantity'],
        'cost' => $data['cost'],
        'received_date' => $data['received_date'],
        'updated_date' => $data['updated_date'] ?? now(),
    ]);

    return $stock;
}

}
