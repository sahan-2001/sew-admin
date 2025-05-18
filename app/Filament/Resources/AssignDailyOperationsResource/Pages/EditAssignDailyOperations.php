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
        $data['show_operation_date'] = !empty($data['working_hours']);
        
        $data['daily_operations'] = $this->getRecord()->lines()->with([
            'assignedEmployees.user',
            'assignedSupervisors.user',
            'assignedProductionMachines',
            'assignedThirdPartyServices'
        ])->get()->map(function ($line) {
            return [
                'production_line_id' => $line->production_line_id,
                'workstation_id' => $line->workstation_id,
                'operation_id' => $line->operation_id,
                'workstation_name' => $line->workstation->name ?? 'N/A',
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

        $data['working_hours'] = $this->getRecord()->assignedWorkingHours()->get()->map(function ($hour) {
            return [
                'operation_date' => $hour->operation_date,
                'start_time' => $hour->start_time,
                'end_time' => $hour->end_time,
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

        // Helper to sync or create related entities
        $this->syncOrCreateRelations($record, 'assignedEmployees', 'user_id', $data['employee_ids'] ?? []);
        $this->syncOrCreateRelations($record, 'assignedSupervisors', 'user_id', $data['supervisor_ids'] ?? []);
        $this->syncOrCreateRelations($record, 'assignedProductionMachines', 'production_machine_id', $data['machine_ids'] ?? []);
        $this->syncOrCreateRelations($record, 'assignedThirdPartyServices', 'third_party_service_id', $data['third_party_service_ids'] ?? []);

        return $record;
    }

    protected function syncOrCreateRelations($record, string $relationName, string $foreignKey, array $ids)
    {
        $syncIds = [];

        foreach ($ids as $id) {
            // Check if related record exists by id
            try {
                $relationModel = $record->$relationName()->getRelated()::findOrFail($id);
                $syncIds[] = $id;
            } catch (ModelNotFoundException $e) {
                // Record does not exist - create new related record with created_by and updated_by
                $newRecord = $record->$relationName()->getRelated()::create([
                    $foreignKey => $id,  // This depends on your table structure, adjust accordingly
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
                $syncIds[] = $newRecord->id;
            }
        }

        $record->$relationName()->sync($syncIds);
    }
}