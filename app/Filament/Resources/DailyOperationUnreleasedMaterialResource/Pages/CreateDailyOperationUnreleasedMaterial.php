<?php

namespace App\Filament\Resources\DailyOperationUnreleasedMaterialResource\Pages;

use App\Models\UMOperation;
use App\Models\UMOperationLine;
use App\Models\UMOperationLineEmployee;
use App\Models\UMOperationLineSupervisor;
use App\Models\UMOperationLineMachine;
use App\Models\UMOperationLineService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\DailyOperationUnreleasedMaterialResource;

class CreateDailyOperationUnreleasedMaterial extends CreateRecord
{
    protected static string $resource = DailyOperationUnreleasedMaterialResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Create the main UMOperation record
        $umOperation = UMOperation::create([
            'order_type' => $data['order_type'],
            'order_id' => $data['order_id'],
            'operation_date' => $data['operation_date'],
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        // Create operation lines and related records
        foreach ($data['daily_operations'] as $operation) {
            $line = UMOperationLine::create([
                'u_m_operation_id' => $umOperation->id, 
                'production_line_id' => $operation['production_line_id'],
                'workstation_id' => $operation['workstation_id'],
                'operation_id' => $operation['operation_id'],
                'sequence' => $operation['sequence'],
                'setup_time' => $operation['setup_time'],
                'run_time' => $operation['run_time'],
                'target_duration' => $operation['target_duration'] ?? null,
                'target' => $operation['target'] ?? null,
                'measurement_unit' => $operation['measurement_unit'] ?? null,
                'status' => 'pending', // Default status for new operations
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Create assigned employees
            foreach ($operation['employee_ids'] as $employeeId) {
                UMOperationLineEmployee::create([
                    'user_id' => $employeeId,
                    'u_m_operation_line_id' => $line->id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            // Create assigned supervisors
            foreach ($operation['supervisor_ids'] ?? [] as $supervisorId) {
                UMOperationLineSupervisor::create([
                    'user_id' => $supervisorId,
                    'u_m_operation_line_id' => $line->id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            // Create assigned machines
            foreach ($operation['machine_ids'] ?? [] as $machineId) {
                UMOperationLineMachine::create([
                    'production_machine_id' => $machineId,
                    'u_m_operation_line_id' => $line->id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            // Create assigned third party services
            foreach ($operation['third_party_service_ids'] ?? [] as $serviceId) {
                UMOperationLineService::create([
                    'third_party_service_id' => $serviceId,
                    'u_m_operation_line_id' => $line->id,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        }

        return $umOperation;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}