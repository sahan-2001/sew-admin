<?php

namespace App\Filament\Resources\AssignDailyOperationsResource\Pages;

use App\Models\AssignDailyOperation;
use App\Models\AssignDailyOperationLine;
use App\Models\AssignDailyOperationLabel;
use App\Models\AssignedEmployee;
use App\Models\AssignedSupervisor;
use App\Models\AssignedProductionMachine;
use App\Models\AssignedWorkingHour;
use App\Models\AssignedThirdPartyService;
use Filament\Actions;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\AssignDailyOperationsResource; 

class CreateAssignDailyOperations extends CreateRecord
{
    protected static string $resource = AssignDailyOperationsResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Check for existing record with the same order type, order ID, and operation date
        $existingRecord = AssignDailyOperation::where('order_type', $data['order_type'])
            ->where('order_id', $data['order_id'])
            ->whereDate('operation_date', Carbon::parse($data['operation_date']))
            ->first();

        if ($existingRecord) {
            Notification::make()
                ->title('Duplicate Record')
                ->danger()
                ->body('A record for this Order Type, Order ID, and Date already exists.')
                ->send();

            $this->form->fill([]); // Clear the form

            return $existingRecord;
        }

        // Create the main AssignDailyOperation record
        $assignDailyOperation = AssignDailyOperation::create([
            'order_type' => $data['order_type'],
            'order_id' => $data['order_id'],
            'operation_date' => $data['operation_date'],
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        // Save the selected labels if they exist
        if (isset($data['selected_label_ids'])) {
            $assignDailyOperation->labels()->sync($data['selected_label_ids']);
        }

        Notification::make()
            ->title('Record Created Successfully')
            ->success()
            ->body('Assigned Daily Operation Created Successfully with ID: ' . $assignDailyOperation->id)
            ->send();

        // Create operation lines and related records
        foreach ($data['daily_operations'] as $operation) {
            $line = AssignDailyOperationLine::create([
                'assign_daily_operation_id' => $assignDailyOperation->id,
                'production_line_id' => $operation['production_line_id'],
                'workstation_id' => $operation['workstation_id'],
                'operation_id' => $operation['operation_id'],
                'machine_setup_time' => $operation['machine_setup_time'],
                'labor_setup_time' => $operation['labor_setup_time'],
                'machine_run_time' => $operation['machine_run_time'],
                'labor_run_time' => $operation['labor_run_time'],
                'target_duration' => $operation['target_duration'] ?? null,
                'target_e' => $operation['target_e'] ?? null,
                'target_m' => $operation['target_m'] ?? null,
                'measurement_unit' => $operation['measurement_unit'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->createRelatedRecords($operation, $line->id, $assignDailyOperation->id, $data['operation_date']);
        }

        return $assignDailyOperation;
    }

    private function createRelatedRecords(array $operation, int $lineId, int $operationId, string $date): void
    {
        foreach ($operation['employee_ids'] as $employeeId) {
            AssignedEmployee::create([
                'user_id' => $employeeId,
                'assign_daily_operation_line_id' => $lineId,
            ]);
        }

        foreach ($operation['supervisor_ids'] ?? [] as $supervisorId) {
            AssignedSupervisor::create([
                'user_id' => $supervisorId,
                'assign_daily_operation_line_id' => $lineId,
            ]);
        }

        foreach ($operation['machine_ids'] ?? [] as $machineId) {
            AssignedProductionMachine::create([
                'production_machine_id' => $machineId,
                'assign_daily_operation_line_id' => $lineId,
            ]);
        }

        foreach ($operation['third_party_service_ids'] ?? [] as $serviceId) {
            AssignedThirdPartyService::create([
                'third_party_service_id' => $serviceId,
                'assign_daily_operation_line_id' => $lineId,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return static::$resource::getUrl('index');
    }
}
