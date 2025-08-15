<?php

namespace App\Filament\Resources\EndOfDayReportResource\Pages;

use App\Filament\Resources\EndOfDayReportResource;
use App\Models\EndOfDayReportOperation;
use App\Models\EndOfDayReport;
use App\Models\EnterPerformanceRecord;
use App\Models\TemporaryOperation;
use App\Models\AssignDailyOperation;
use App\Models\AssignDailyOperationLine;
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
        $updatedPerformanceRecordIds = [];
        $updatedTemporaryOperationIds = [];
        $operationLineIds = [];
        $assignDailyOperationIds = [];

        foreach ($records as $record) {
            if ($record['type'] === 'performance') {
                // Handle performance records
                $data = $record['data'];
                
                EndOfDayReportOperation::create([
                    'end_of_day_report_id' => $this->record->id,
                    'enter_performance_record_id' => $data['id'],
                    'assign_daily_operation_id' => $data['assign_daily_operation_id'],
                    'operation_line_id' => $data['assign_daily_operation_line_id'] ?? null,
                    'temporary_operation_id' => null,
                ]);

                $updatedPerformanceRecordIds[] = $data['id'];
                $operationLineIds[] = $data['assign_daily_operation_line_id'];
                $assignDailyOperationIds[] = $data['assign_daily_operation_id'];
            } 
            elseif ($record['type'] === 'temporary') {
                // Handle temporary operations
                $data = $record['data'];
                
                EndOfDayReportOperation::create([
                    'end_of_day_report_id' => $this->record->id,
                    'temporary_operation_id' => $data['id'],
                    'enter_performance_record_id' => null,
                    'assign_daily_operation_id' => null,
                    'operation_line_id' => null,
                ]);

                $updatedTemporaryOperationIds[] = $data['id'];
            }
        }

        // Update status of related EnterPerformanceRecord models
        if (!empty($updatedPerformanceRecordIds)) {
            EnterPerformanceRecord::whereIn('id', $updatedPerformanceRecordIds)
                ->update(['status' => 'reported']);
        }

        // Update status of related TemporaryOperation models
        if (!empty($updatedTemporaryOperationIds)) {
            TemporaryOperation::whereIn('id', $updatedTemporaryOperationIds)
                ->update(['status' => 'reported']);
        }

        // Update status of related AssignDailyOperationLine models
        if (!empty($operationLineIds)) {
            AssignDailyOperationLine::whereIn('id', $operationLineIds)
                ->update(['status' => 'reported']);
        }

        // Update status of related AssignDailyOperation models
        if (!empty($assignDailyOperationIds)) {
            AssignDailyOperation::whereIn('id', array_unique($assignDailyOperationIds))
                ->update(['status' => 'recorded']);
        }

        // Update count of recorded operations in the report
        $this->record->update([
            'recorded_operations_count' => count($records),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}