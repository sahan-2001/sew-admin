<?php

namespace App\Filament\Resources\FinalProductQCResource\Pages;

use App\Filament\Resources\FinalProductQCResource;
use App\Models\FinalProductQC;
use App\Models\FinalProductQCLabel;
use App\Models\CuttingLabel;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class CreateFinalProductQC extends CreateRecord
{
    protected static string $resource = FinalProductQCResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            \DB::beginTransaction();

            // Create the main QC record
            $qcRecord = FinalProductQC::create([
                'order_type' => $data['order_type'],
                'order_id' => $data['order_id'],
                'qc_officer_id' => $data['qc_officer_id'],
                'inspected_date' => $data['inspected_date'],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Process passed labels
            $passedLabels = $data['selected_labels_qc_p'] ?? [];
            foreach ($passedLabels as $labelId) {
                FinalProductQCLabel::create([
                    'final_product_qc_id' => $qcRecord->id,
                    'cutting_label_id' => $labelId,
                    'result' => 'pass',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                // Update cutting label status
                CuttingLabel::where('id', $labelId)->update([
                    'status' => 'qc_passed'
                ]);
            }

            // Process failed labels
            $failedLabels = $data['selected_labels_qc_f'] ?? [];
            foreach ($failedLabels as $labelId) {
                FinalProductQCLabel::create([
                    'final_product_qc_id' => $qcRecord->id,
                    'cutting_label_id' => $labelId,
                    'result' => 'fail', 
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                // Update cutting label status based on action
                $cuttingLabel = CuttingLabel::find($labelId);
                if ($cuttingLabel) {
                    $cuttingLabel->update([
                        'status' => 'qc_failed'
                    ]);

                    // Handle failed item action
                    if (isset($data['failed_item_action'])) {
                        switch ($data['failed_item_action']) {
                            case 'cutting_section':
                                if (isset($data['cutting_station_id'])) {
                                    $cuttingLabel->update([
                                        'cutting_station_id' => $data['cutting_station_id'],
                                        'status' => 'sent_to_cutting'
                                    ]);
                                }
                                break;
                            
                            case 'sawing_section':
                                if (isset($data['sawing_operation_id'])) {
                                    $cuttingLabel->update([
                                        'sawing_operation_id' => $data['sawing_operation_id'],
                                        'status' => 'sent_to_sawing'
                                    ]);
                                }
                                break;
                        }
                    }
                }
            }

            $orderType = $data['order_type'];
            $orderId = $data['order_id'];

            if ($orderType === 'customer_order') {
                \App\Models\CustomerOrder::where('order_id', $orderId)->update(['status' => 'final_qc_recorded']);
            } elseif ($orderType === 'sample_order') {
                \App\Models\SampleOrder::where('order_id', $orderId)->update(['status' => 'final_qc_recorded']);
            }

            \DB::commit();

            return $qcRecord;

        } catch (\Exception $e) {
            \DB::rollBack();
            
            Notification::make()
                ->title('Error creating QC record')
                ->body($e->getMessage())
                ->danger()
                ->send();
            
            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Final Product QC Created')
            ->body('The final product quality check has been successfully recorded.');
    }
}
