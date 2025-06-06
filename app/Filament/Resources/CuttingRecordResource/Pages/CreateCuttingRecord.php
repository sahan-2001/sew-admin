<?php

namespace App\Filament\Resources\CuttingRecordResource\Pages;

use App\Filament\Resources\CuttingRecordResource;
use App\Models\CuttingRecord;
use App\Models\ReleaseMaterialLine;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateCuttingRecord extends CreateRecord
{
    protected static string $resource = CuttingRecordResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Create the main cutting record
            $record = CuttingRecord::create([
                'operation_date' => $data['operation_date'],
                'order_type' => $data['order_type'],
                'order_id' => $data['order_id'],
                'cutting_station_id' => $data['cutting_station_id'],
                'release_material_id' => $data['release_material_id'],
                'operated_time_from' => $data['operated_time_from'],
                'operated_time_to' => $data['operated_time_to'],
            ]);

            // Attach employees
            if (!empty($data['employees'])) {
                $record->employees()->createMany($data['employees']);
            }

            // Create quality controls
            if (!empty($data['qualityControls'])) {
                $record->qualityControls()->createMany($data['qualityControls']);
            }

            // Create waste records
            $record->wasteRecords()->createMany(
                array_filter(array_map(function ($waste) {
                    return [
                        'item_id' => $waste['inv_item_id'] ?? null,
                        'amount' => $waste['inv_amount'] ?? null,
                        'unit' => $waste['inv_unit'] ?? null,
                        'location_id' => $waste['inv_location_id'] ?? null,
                    ];
                }, $data['waste_records']), fn($row) => array_filter($row))
            );

            // Create non-inventory waste
            $record->nonInventoryWaste()->createMany(
                array_filter(array_map(function ($waste) {
                    return [
                        'item_id' => $waste['non_i_item_id'] ?? null,
                        'amount' => $waste['non_i_amount'] ?? null,
                        'unit' => $waste['non_i_unit'] ?? null,
                    ];
                }, $data['non_inventory_waste']), fn($row) => array_filter($row))
            );

            // Create by-products
            $record->byProductRecords()->createMany(
                array_filter(array_map(function ($byProduct) {
                    return [
                        'item_id' => $byProduct['by_item_id'] ?? null,
                        'amount' => $byProduct['by_amount'] ?? null,
                        'unit' => $byProduct['by_unit'] ?? null,
                        'location_id' => $byProduct['by_location_id'] ?? null,
                    ];
                }, $data['by_product_records']), fn($row) => array_filter($row))
            );

            // Update release material lines with cut quantities
            if (!empty($data['fetched_release_material_items'])) {
                foreach ($data['fetched_release_material_items'] as $item) {
                    if (isset($item['cut_quantity']) && $item['cut_quantity'] > 0) {
                        $releaseMaterialLine = ReleaseMaterialLine::where('release_material_id', $data['release_material_id'])
                            ->whereHas('item', function($query) use ($item) {
                                $query->where('item_code', $item['item_code']);
                            })
                            ->first();

                        if ($releaseMaterialLine) {
                            // Update the cut quantity and remaining quantity
                            $newCutQuantity = $releaseMaterialLine->cut_quantity + $item['cut_quantity'];

                            $releaseMaterialLine->update([
                                'cut_quantity' => $newCutQuantity,
                            ]);
                        }
                    }
                }
            }

            // Create order items and labels
            if (!empty($data['fetched_order_items'])) {
                foreach ($data['fetched_order_items'] as $item) {
                    $orderItem = $record->orderItems()->create([
                        'item_id' => $item['item_id'],
                        'item_type' => $data['order_type'],
                        'quantity' => $item['no_of_pieces'] ?? 0,
                        'start_label' => $item['start_label'] ?? null,
                        'end_label' => $item['end_label'] ?? null,
                    ]);

                    // Create variations if they exist
                    if (!empty($item['variations'])) {
                        foreach ($item['variations'] as $variation) {
                            $orderVariation = $orderItem->variations()->create([
                                'cutting_record_id' => $record->id,
                                'variation_id' => $variation['var_item_id'] ?? null,
                                'variation_type' => $data['order_type'] ?? null,
                                'quantity' => $variation['no_of_pieces_var'] ?? 0,
                                'start_label' => $variation['start_label_var'] ?? null,
                                'end_label' => $variation['end_label_var'] ?? null,
                            ]);

                            $this->generateLabels($orderVariation, $variation, $record, $data['order_id'], $data['order_type']);
                        }
                    } else {
                        $this->generateLabels($orderItem, $item, $record, $data['order_id'], $data['order_type']);
                    }
                }
            }

            return $record;
        });
    }

    protected function generateLabels($parentModel, $itemData, $cuttingRecord, $orderId, $orderType)
    {
        // Determine if we're dealing with an order item or variation
        $isVariation = $parentModel instanceof \App\Models\CuttingOrderVariation;
        
        // Get the appropriate quantity
        $quantity = $isVariation 
            ? ($itemData['quantity'] ?? $itemData['no_of_pieces_var'] ?? 0)
            : ($parentModel->quantity ?? $itemData['no_of_pieces'] ?? 0);

        // Ensure we have at least 1 label if there's any data
        $quantity = max($quantity, 1);

        if ($quantity > 0) {
            // Get all required IDs
            $cuttingRecordId = $cuttingRecord->id;
            $orderItemId = $isVariation 
                ? $parentModel->order_item_id 
                : $parentModel->id;
            $orderVariationId = $isVariation ? $parentModel->id : null;

            // Determine padding length based on count
            $paddingLength = strlen((string) $quantity);

            for ($i = 1; $i <= $quantity; $i++) {
                $paddedIndex = str_pad($i, $paddingLength, '0', STR_PAD_LEFT);

                // Build label in the format: SO/CO-OrderID-CuttingRecordID-OrderItemID-OrderVariationID-Index
                $labelParts = [
                    $orderType,       // SO or CO
                    $orderId,         // Order ID
                    $cuttingRecordId,  // Cutting record ID
                    $orderItemId,     // Order item ID
                ];

                // Add variation ID only if it exists
                if ($orderVariationId) {
                    $labelParts[] = $orderVariationId;
                }

                // Add the index
                $labelParts[] = $paddedIndex;

                // Join all parts with hyphens
                $label = implode('-', $labelParts);

                $cuttingRecord->cutPieceLabels()->create([
                    'label' => $label,
                    'order_id' => $orderId,
                    'order_type' => $orderType,
                    'quantity' => 1,
                    'order_item_id' => $orderItemId,
                    'order_variation_id' => $orderVariationId,
                    'parent_type' => get_class($parentModel),
                    'parent_id' => $parentModel->id,
                ]);
            }
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        \Filament\Notifications\Notification::make()
            ->title('Cutting record created successfully')
            ->success()
            ->send();
        

        $record = $this->record;

        // 1. Update Order Status
        if ($record->order_type === 'customer_order') {
            $order = \App\Models\CustomerOrder::find($record->order_id);
        } elseif ($record->order_type === 'sample_order') {
            $order = \App\Models\SampleOrder::find($record->order_id);
        }

        if (isset($order)) {
            $order->status = 'cut';
            $order->save();
        }

    }
}