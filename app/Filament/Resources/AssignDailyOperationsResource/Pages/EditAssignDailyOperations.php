<?php

namespace App\Filament\Resources\AssignDailyOperationsResource\Pages;

use App\Filament\Resources\AssignDailyOperationsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;


class EditAssignDailyOperations extends EditRecord
{
    protected static string $resource = AssignDailyOperationsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $data = $this->getRecord()->toArray();
        
        $data['order_type'] = $this->getRecord()->order_type;
        $data['order_id'] = $this->getRecord()->order_id;
        
        // Load lines with nested relations
        $data['daily_operations'] = $this->getRecord()->lines()->with([
            'productionLine',
            'workstation',
            'operation',
            'assignedEmployees.user',
            'assignedSupervisors.user',
            'assignedProductionMachines',
            'assignedThirdPartyServices'
        ])->get()->map(function ($line) {
            return [
                'id' => $line->id,
                'production_line_id' => $line->production_line_id,
                'production_line_name' => $line->productionLine->name ?? 'N/A',
                'workstation_id' => $line->workstation_id,
                'workstation_name' => $line->workstation->name ?? 'N/A',
                'operation_id' => $line->operation_id,
                'operation_description' => $line->operation->description ?? 'N/A',
                'employee_ids' => $line->assignedEmployees->pluck('user_id')->toArray(),
                'supervisor_ids' => $line->assignedSupervisors->pluck('user_id')->toArray(),
                'machine_ids' => $line->assignedProductionMachines->pluck('production_machine_id')->toArray(),
                'third_party_service_ids' => $line->assignedThirdPartyServices->pluck('third_party_service_id')->toArray(),
                'setup_time' => $line->setup_time,
                'run_time' => $line->run_time,
                'target_duration' => $line->target_duration,
                'target' => $line->target,
                'measurement_unit' => $line->measurement_unit,
            ];
        })->toArray();


        $this->form->fill($data);

        $this->callHook('afterFill');
    }


    // Add this method to handle the update, creating missing related records if needed
    protected function handleRecordUpdate($record, array $data): Model
    {
        // Update main record
        $record->update($data);

        // Update the daily operations and their related records
        foreach ($data['daily_operations'] as $operation) {
            // Find or create the operation line
            $line = $record->lines()->updateOrCreate(
                ['id' => $operation['id'] ?? null], // If ID exists, update; otherwise create new
                [
                    'production_line_id' => $operation['production_line_id'],
                    'workstation_id' => $operation['workstation_id'],
                    'operation_id' => $operation['operation_id'],
                    'setup_time' => $operation['setup_time'],
                    'run_time' => $operation['run_time'],
                    'target_duration' => $operation['target_duration'],
                    'target' => $operation['target'],
                    'measurement_unit' => $operation['measurement_unit'],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]
            );

            // Sync or create related entities (employees, supervisors, etc.)
            $this->syncOrCreateRelations($line, 'assignedEmployees', 'user_id', $operation['employee_ids'] ?? []);
            $this->syncOrCreateRelations($line, 'assignedSupervisors', 'user_id', $operation['supervisor_ids'] ?? []);
            $this->syncOrCreateRelations($line, 'assignedProductionMachines', 'production_machine_id', $operation['machine_ids'] ?? []);
            $this->syncOrCreateRelations($line, 'assignedThirdPartyServices', 'third_party_service_id', $operation['third_party_service_ids'] ?? []);
        }

        return $record;
    }


    protected function syncOrCreateRelations($line, string $relationName, string $foreignKey, array $ids)
    {
        // Delete old relations not in the new list
        $line->$relationName()->whereNotIn($foreignKey, $ids)->delete();

        // Add new relations if not existing
        foreach ($ids as $id) {
            $line->$relationName()->firstOrCreate(
                [$foreignKey => $id],
                ['created_by' => auth()->id(), 'updated_by' => auth()->id()]
            );
        }
    }

}