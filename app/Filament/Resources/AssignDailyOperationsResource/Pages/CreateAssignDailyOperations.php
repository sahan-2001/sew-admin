<?php

// app/Filament/Resources/AssignDailyOperationsResource/Pages/CreateAssignDailyOperations.php
namespace App\Filament\Resources\AssignDailyOperationsResource\Pages;

use App\Models\AssignDailyOperation;
use App\Models\AssignDailyOperationLine;
use App\Models\AssignedEmployee;
use App\Models\AssignedSupervisor;
use App\Models\AssignedProductionMachine;
use App\Models\AssignedWorkingHour;
use App\Models\AssignedThirdPartyService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\AssignDailyOperationsResource; 

class CreateAssignDailyOperations extends CreateRecord
{
    protected static string $resource = AssignDailyOperationsResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Create the main AssignDailyOperation record
        $assignDailyOperation = AssignDailyOperation::create([
            'order_type' => $data['order_type'],
            'order_id' => $data['order_id'],
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        // Create operation lines and related records
        foreach ($data['daily_operations'] as $operation) {
            $line = AssignDailyOperationLine::create([
                'assign_daily_operation_id' => $assignDailyOperation->id,
                'production_line_id' => $operation['production_line_id'],
                'workstation_id' => $operation['workstation_id'],
                'operation_id' => $operation['operation_id'],
                'setup_time' => $operation['setup_time'],
                'run_time' => $operation['run_time'],
                'target_duration' => $operation['target_durattion'], 
                'target' => $operation['target'],
                'measurement_unit' => $operation['measurement_unit'],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Create assigned employees
            foreach ($operation['employee_ids'] as $employeeId) {
                AssignedEmployee::create([
                    'user_id' => $employeeId,
                    'assign_daily_operation_line_id' => $line->id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            // Create assigned supervisors
            foreach ($operation['supervisor_ids'] ?? [] as $supervisorId) {
                AssignedSupervisor::create([
                    'user_id' => $supervisorId,
                    'assign_daily_operation_line_id' => $line->id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            // Create assigned machines
            foreach ($operation['machine_ids'] ?? [] as $machineId) {
                AssignedProductionMachine::create([
                    'production_machine_id' => $machineId,
                    'assign_daily_operation_line_id' => $line->id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            // Create assigned third party services
            foreach ($operation['third_party_service_ids'] ?? [] as $serviceId) {
                AssignedThirdPartyService::create([
                    'third_party_service_id' => $serviceId,
                    'assign_daily_operation_line_id' => $line->id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            // Create assigned working hours
            if (!empty($data['working_hours'])) {
                foreach ($data['working_hours'] as $workingHour) {
                    AssignedWorkingHour::create([
                        'assign_daily_operation_id' => $assignDailyOperation->id,
                        'operation_date' => $data['operation_date'],
                        'start_time' => $workingHour['start_time'],
                        'end_time' => $workingHour['end_time'],
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }
            }

        }

        return $assignDailyOperation;
    }
}