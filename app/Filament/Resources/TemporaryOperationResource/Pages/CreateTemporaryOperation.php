<?php

namespace App\Filament\Resources\TemporaryOperationResource\Pages;

use App\Models\TemporaryOperation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\TemporaryOperationResource;

class CreateTemporaryOperation extends CreateRecord
{
    protected static string $resource = TemporaryOperationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Create the main temporary operation record
        $tempOperation = TemporaryOperation::create([
            'order_type' => $data['order_type'],
            'order_id' => $data['order_id'],
            'description' => $data['description'] ?? null,
            'production_line_id' => $data['production_line_id'] ?? null,
            'workstation_id' => $data['workstation_id'] ?? null,
            'operation_date' => $data['operation_date'] ?? now(),
            'machine_setup_time' => $data['machine_setup_time'] ?? 0,
            'machine_run_time' => $data['machine_run_time'] ?? 0,
            'labor_setup_time' => $data['labor_setup_time'] ?? 0,
            'labor_run_time' => $data['labor_run_time'] ?? 0,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        // Attach employees
        if (!empty($data['employee_ids'])) {
            $tempOperation->employees()->attach($data['employee_ids'], [
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        // Attach supervisors
        if (!empty($data['supervisor_ids'])) {
            $tempOperation->supervisors()->attach($data['supervisor_ids'], [
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        // Attach machines
        if (!empty($data['machine_ids'])) {
            $tempOperation->productionMachines()->attach($data['machine_ids'], [
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        // Attach services
        if (!empty($data['third_party_service_ids'])) {
            $tempOperation->services()->attach($data['third_party_service_ids'], [
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }

        return $tempOperation;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}