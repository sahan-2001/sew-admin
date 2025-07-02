<?php

namespace App\Filament\Resources\CuttingRecordResource\Pages;

use App\Filament\Resources\CuttingRecordResource;
use App\Models\CuttingRecord;
use App\Models\ReleaseMaterialLine;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class EditCuttingRecord extends EditRecord
{
    protected static string $resource = CuttingRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('Print Report')
                ->label('Print Report')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('cutting-records.print', ['cutting_record' => $this->record->id]))
                ->openUrlInNewTab(),
            Actions\Action::make('Print Labels')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('cutting-records.print-labels', $this->record))
                ->openUrlInNewTab(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load related data for the form
        $record = $this->getRecord();
        
        // Pre-load release material items with remaining quantities
        if ($record->release_material_id) {
            $releaseMaterial = $record->releaseMaterial()->with(['lines' => function($query) {
                $query->where('quantity', '>', 0);
            }, 'lines.item', 'lines.location'])->first();
            
            if ($releaseMaterial) {
                $data['available_release_materials'] = [
                    $releaseMaterial->id => $releaseMaterial->cuttingStation->name . ' | ' . 
                                          $releaseMaterial->created_at->format('Y-m-d') . ' | ' . 
                                          'Remaining: ' . $releaseMaterial->lines->sum(function($line) {
                                              return $line->quantity - ($line->cut_quantity ?? 0);
                                          })
                ];
                
                $data['fetched_release_material_items'] = $releaseMaterial->lines->map(function($line) {
                    $cutQuantity = $line->cut_quantity ?? 0;
                    $remainingQuantity = number_format($line->quantity - $cutQuantity, 2, '.', '');
                    
                    return [
                        'item_code' => $line->item->item_code ?? 'N/A',
                        'item_name' => $line->item->name ?? 'N/A',
                        'remaining_quantity' => $remainingQuantity,
                        'uom' => $line->item->uom ?? 'N/A',
                        'location' => $line->location->name ?? 'N/A',
                        'release_material_line_id' => $line->id,
                        'original_quantity' => $line->quantity,
                        'cut_quantity' => 0, // Reset to 0 for edit to prevent duplicate counting
                    ];
                })->toArray();
            }
        }
        
        // Pre-load order items
        $orderItems = [];
        if ($record->order_type === 'customer_order') {
            $customerOrderItems = \App\Models\CustomerOrderDescription::with('variationItems')
                ->where('customer_order_id', $record->order_id)
                ->get();

            $orderItems = $customerOrderItems->map(function ($item) use ($record) {
                $cuttingItem = $record->orderItems()->where('item_id', $item->id)->first();
                
                $variations = [];
                if ($item->variationItems->isNotEmpty()) {
                    foreach ($item->variationItems as $variation) {
                        $cuttingVariation = $record->variations()
                            ->where('variation_id', $variation->id)
                            ->first();
                            
                        $variations[] = [
                            'var_item_id' => $variation->id,
                            'var_item_name' => $variation->variation_name,
                            'var_quantity' => $variation->quantity,
                            'no_of_pieces_var' => $cuttingVariation->quantity ?? 0,
                            'start_label_var' => '',
                            'end_label_var' => '',
                        ];
                    }
                }
                
                return [
                    'item_id' => $item->id,
                    'item_name' => $item->item_name,
                    'quantity' => $item->quantity,
                    'no_of_pieces' => $cuttingItem ? $cuttingItem->quantity : 0,
                    'variations' => $variations,
                    'start_label' => '',
                    'end_label' => '',
                ];
            })->toArray();
        } elseif ($record->order_type === 'sample_order') {
            $sampleOrderItems = \App\Models\SampleOrderItem::with('variations')
                ->where('sample_order_id', $record->order_id)
                ->get();

            $orderItems = $sampleOrderItems->map(function ($item) use ($record) {
                $cuttingItem = $record->orderItems()->where('item_id', $item->id)->first();
                
                $variations = [];
                if ($item->variations->isNotEmpty()) {
                    foreach ($item->variations as $variation) {
                        $cuttingVariation = $record->variations()
                            ->where('variation_id', $variation->id)
                            ->first();
                            
                        $variations[] = [
                            'var_item_id' => $variation->id,
                            'var_item_name' => $variation->variation_name,
                            'var_quantity' => $variation->quantity,
                            'no_of_pieces_var' => $cuttingVariation->quantity ?? 0,
                            'start_label_var' => '',
                            'end_label_var' => '',
                        ];
                    }
                }
                
                return [
                    'item_id' => $item->id,
                    'item_name' => $item->item_name,
                    'quantity' => $item->quantity,
                    'no_of_pieces' => $cuttingItem ? $cuttingItem->quantity : 0,
                    'variations' => $variations,
                    'start_label' => '',
                    'end_label' => '',
                ];
            })->toArray();
        }
        
        $data['fetched_order_items'] = $orderItems;
        
        // Calculate grand total pieces
        $grandTotal = 0;
        foreach ($orderItems as $item) {
            if (!empty($item['variations'])) {
                foreach ($item['variations'] as $variation) {
                    $grandTotal += (int)($variation['no_of_pieces_var'] ?? 0);
                }
            } else {
                $grandTotal += (int)($item['no_of_pieces'] ?? 0);
            }
        }
        $data['grand_total_pieces'] = $grandTotal;
        
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // Update the main cutting record
            $record->update([
                'operation_date' => $data['operation_date'],
                'operated_time_from' => $data['operated_time_from'],
                'operated_time_to' => $data['operated_time_to'],
            ]);
            
            // Sync employees
            $record->employees()->delete();
            if (!empty($data['employees'])) {
                $record->employees()->createMany($data['employees']);
            }
            
            // Sync quality controls
            $record->qualityControls()->delete();
            if (!empty($data['qualityControls'])) {
                $record->qualityControls()->createMany($data['qualityControls']);
            }
            
            // Sync waste records
            $record->wasteRecords()->delete();
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
            
            // Sync non-inventory waste
            $record->nonInventoryWaste()->delete();
            $record->nonInventoryWaste()->createMany(
                array_filter(array_map(function ($waste) {
                    return [
                        'item_id' => $waste['non_i_item_id'] ?? null,
                        'amount' => $waste['non_i_amount'] ?? null,
                        'unit' => $waste['non_i_unit'] ?? null,
                    ];
                }, $data['non_inventory_waste']), fn($row) => array_filter($row))
            );
            
            // Sync by-products
            $record->byProductRecords()->delete();
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
            
            // Update release material lines with new cut quantities
            if (!empty($data['fetched_release_material_items'])) {
                foreach ($data['fetched_release_material_items'] as $item) {
                    if (isset($item['cut_quantity']) && $item['cut_quantity'] > 0) {
                        $releaseMaterialLine = ReleaseMaterialLine::where('release_material_id', $record->release_material_id)
                            ->whereHas('item', function($query) use ($item) {
                                $query->where('item_code', $item['item_code']);
                            })
                            ->first();

                        if ($releaseMaterialLine) {
                            // Calculate the difference from the original cut quantity
                            $originalCutQuantity = $releaseMaterialLine->cut_quantity - ($item['original_cut_quantity'] ?? 0);
                            $newCutQuantity = $originalCutQuantity + $item['cut_quantity'];

                            $releaseMaterialLine->update([
                                'cut_quantity' => $newCutQuantity,
                            ]);
                        }
                    }
                }
            }
            
            // Update order items and labels
            $record->orderItems()->delete();
            $record->variations()->delete();
            $record->cutPieceLabels()->delete();
            
            if (!empty($data['fetched_order_items'])) {
                foreach ($data['fetched_order_items'] as $item) {
                    $orderItem = $record->orderItems()->create([
                        'item_id' => $item['item_id'],
                        'item_type' => $data['order_type'],
                        'quantity' => $item['no_of_pieces'] ?? 0,
                    ]);

                    // Create variations if they exist
                    if (!empty($item['variations'])) {
                        foreach ($item['variations'] as $variation) {
                            $orderVariation = $orderItem->variations()->create([
                                'cutting_record_id' => $record->id,
                                'variation_id' => $variation['var_item_id'] ?? null,
                                'variation_type' => $data['order_type'] ?? null,
                                'quantity' => $variation['no_of_pieces_var'] ?? 0,
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
        $isVariation = $parentModel instanceof \App\Models\CuttingOrderVariation;

        $quantity = $isVariation 
            ? ($itemData['quantity'] ?? $itemData['no_of_pieces_var'] ?? 0)
            : ($parentModel->quantity ?? $itemData['no_of_pieces'] ?? 0);

        $quantity = max($quantity, 1);

        if ($quantity > 0) {
            $cuttingRecordId = $cuttingRecord->id;
            $orderItemId = $isVariation 
                ? $parentModel->order_item_id 
                : $parentModel->id;
            $orderVariationId = $isVariation ? $parentModel->id : null;

            $paddingLength = strlen((string) $quantity);

            for ($i = 1; $i <= $quantity; $i++) {
                $paddedIndex = str_pad($i, $paddingLength, '0', STR_PAD_LEFT);

                $barcodeIdParts = [
                    strtoupper(substr($orderType, 0, 3)),
                    $orderId,
                    $cuttingRecordId,
                    $orderItemId,
                ];

                if (!empty($orderVariationId)) {
                    $barcodeIdParts[] = $orderVariationId;
                }

                $barcodeIdParts[] = $paddedIndex;

                $barcodeId = implode('-', $barcodeIdParts);

                // Full label for reference
                $labelParts = [
                    $orderType,
                    $orderId,
                    $cuttingRecordId,
                    $orderItemId,
                ];

                if ($orderVariationId) {
                    $labelParts[] = $orderVariationId;
                }

                $labelParts[] = $paddedIndex;

                $fullLabel = implode('-', $labelParts);

                // Generate barcode image for the barcode ID (not full label)
                $barcodeImage = DNS1D::getBarcodePNG($barcodeId, 'C128', 3, 100);

                // Save image as PNG file
                $fileName = 'barcodes/' . $barcodeId . '.png';
                $filePath = storage_path('app/public/' . $fileName);

                // Ensure directory exists
                Storage::disk('public')->makeDirectory('barcodes');

                file_put_contents($filePath, base64_decode($barcodeImage));

                // Store in DB
                $cuttingRecord->cutPieceLabels()->create([
                    'label' => $fullLabel,
                    'barcode_id' => $barcodeId,
                    'barcode' => 'storage/' . $fileName, 
                    'status' => 'Non-completed',
                    'order_id' => $orderId,
                    'order_type' => $orderType,
                    'quantity' => $paddedIndex,
                    'order_item_id' => $orderItemId,
                    'order_variation_id' => $orderVariationId,
                    'parent_type' => get_class($parentModel),
                    'parent_id' => $parentModel->id,
                ]);
            }
        }
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title('Cutting record updated successfully')
            ->success()
            ->send();
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}