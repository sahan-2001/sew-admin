<?php

namespace App\Filament\Resources\DailyOperationUnreleasedMaterialResource\Pages;

use App\Models\UMOperation;
use App\Models\UMOperationLine;
use App\Models\UMOperationLineEmployee;
use App\Models\UMOperationLineSupervisor;
use App\Models\UMOperationLineMachine;
use App\Models\UMOperationLineService;
use Filament\Actions;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\DailyOperationUnreleasedMaterialResource;

class CreateDailyOperationUnreleasedMaterial extends CreateRecord
{
    protected static string $resource = DailyOperationUnreleasedMaterialResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Check for existing record with same order type, order ID, and date
        $existingRecord = UMOperation::where('order_type', $data['order_type'])
            ->where('order_id', $data['order_id'])
            ->whereDate('created_at', Carbon::today())
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

        // Create the main UMOperation record
        $umOperation = UMOperation::create([
            'order_type' => $data['order_type'],
            'order_id' => $data['order_id'],
            'operation_date' => $data['operation_date'],
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Record Created Successfully')
            ->success()
            ->body('UM Operation Created Successfully with ID: ' . $umOperation->id)
            ->send();

        // Create operation lines and related records
        foreach ($data['daily_operations'] as $operation) {
            $line = UMOperationLine::create([
                'u_m_operation_id' => $umOperation->id, 
                'production_line_id' => $operation['production_line_id'],
                'workstation_id' => $operation['workstation_id'],
                'operation_id' => $operation['operation_id'],
                'machine_setup_time' => $operation['machine_setup_time'],
                'machine_run_time' => $operation['machine_run_time'],
                'labor_setup_time' => $operation['labor_setup_time'],
                'labor_run_time' => $operation['labor_run_time'],
                'target_duration' => $operation['target_duration'] ?? null,
                'target' => $operation['target'] ?? null,
                'measurement_unit' => $operation['measurement_unit'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->createRelatedRecords($operation, $line->id);
        }

        return $umOperation;
    }

    private function createRelatedRecords(array $operation, int $lineId): void
    {
        foreach ($operation['employee_ids'] as $employeeId) {
            UMOperationLineEmployee::create([
                'user_id' => $employeeId,
                'u_m_operation_line_id' => $lineId,
            ]);
        }

        foreach ($operation['supervisor_ids'] ?? [] as $supervisorId) {
            UMOperationLineSupervisor::create([
                'user_id' => $supervisorId,
                'u_m_operation_line_id' => $lineId,
                
            ]);
        }

        foreach ($operation['machine_ids'] ?? [] as $machineId) {
            UMOperationLineMachine::create([
                'production_machine_id' => $machineId,
                'u_m_operation_line_id' => $lineId,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        foreach ($operation['third_party_service_ids'] ?? [] as $serviceId) {
            UMOperationLineService::create([
                'third_party_service_id' => $serviceId,
                'u_m_operation_line_id' => $lineId,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
