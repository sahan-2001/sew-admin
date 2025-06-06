<?php

namespace App\Filament\Resources\ReleaseMaterialResource\Pages;

use App\Filament\Resources\ReleaseMaterialResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Stock;

class CreateReleaseMaterial extends CreateRecord
{
    protected static string $resource = ReleaseMaterialResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        // 1. Update Order Status
        if ($record->order_type === 'customer_order') {
            $order = \App\Models\CustomerOrder::find($record->order_id);
        } elseif ($record->order_type === 'sample_order') {
            $order = \App\Models\SampleOrder::find($record->order_id);
        }

        if (isset($order)) {
            $order->status = 'material released';
            $order->save();
        }

        // 2. Deduct Quantity from Stock
        foreach ($record->lines as $line) {
            $stock = Stock::find($line->stock_id);
            if ($stock && $stock->quantity >= $line->quantity) {
                $stock->quantity -= $line->quantity;
                $stock->save();
            } else {
                // Handle overdrawn stock edge case if necessary
            }
        }
    }
}
