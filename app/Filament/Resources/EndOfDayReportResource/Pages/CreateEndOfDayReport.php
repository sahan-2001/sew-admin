<?php

namespace App\Filament\Resources\EndOfDayReportResource\Pages;

use App\Filament\Resources\EndOfDayReportResource;
use App\Models\EndOfDayReportOperation;
use App\Models\EndOfDayReport;
use App\Models\EnterPerformanceRecord;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateEndOfDayReport extends CreateRecord
{
    protected static string $resource = EndOfDayReportResource::class;
    protected bool $shouldRedirect = false;

    protected function beforeCreate(): void
    {
        $date = $this->data['operated_date'] ?? null;

        if ($date && EndOfDayReport::whereDate('operated_date', $date)->exists()) {
            Notification::make()
                ->title('Duplicate Report')
                ->body("An End of Day Report already exists for {$date}.")
                ->danger()
                ->duration(5000)
                ->persistent()
                ->send();

            $this->form->fill([
                'operated_date' => null,
                'matching_record_ids' => null,
                'matching_records_count' => 0,
                'matching_records_full' => [],
            ]);

            $this->shouldRedirect = true;

            $this->halt(); 
        }
    }
    
    protected function afterCreate(): void
    {
        $records = $this->data['matching_records_full'] ?? [];
        $updatedRecordIds = [];

        foreach ($records as $record) {
            if (isset($record['id'], $record['assign_daily_operation_id'], $record['assign_daily_operation_line_id'])) {
                // Create EndOfDayReportOperation record
                EndOfDayReportOperation::create([
                    'end_of_day_report_id' => $this->record->id,
                    'enter_performance_record_id' => $record['id'],
                    'assign_daily_operation_id' => $record['assign_daily_operation_id'],
                    'operation_line_id' => $record['assign_daily_operation_line_id'] ?? 0,
                ]);
                
                // Collect IDs to update
                $updatedRecordIds[] = $record['id'];
            }
        }

        // Update status of all related EnterPerformanceRecord models
        if (!empty($updatedRecordIds)) {
            EnterPerformanceRecord::whereIn('id', $updatedRecordIds)
                ->update(['status' => 'reported']);
        }

        $this->record->update([
            'recorded_operations_count' => count($records),
        ]);
    }
}
