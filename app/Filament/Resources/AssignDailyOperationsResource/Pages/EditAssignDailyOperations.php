<?php

namespace App\Filament\Resources\AssignDailyOperationsResource\Pages;

use App\Filament\Resources\AssignDailyOperationsResource;
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
        $data['operation_date'] = $this->getRecord()->operation_date;

        // Load lines with nested relations
        $data['daily_operations'] = $this->getRecord()->lines()->with([
            'productionLine',
            'workstation',
            'operation',
            'assignedEmployees.user',
            'assignedSupervisors.user',
            'assignedProductionMachines',
            'assignedThirdPartyServices',
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
                'machine_setup_time' => $line->machine_setup_time,
                'machine_run_time' => $line->machine_run_time,
                'labor_setup_time' => $line->labor_setup_time,
                'labor_run_time' => $line->labor_run_time,
                'target_duration' => $line->target_duration,
                'target_e' => $line->target_e,
                'target_m' => $line->target_m,
                'measurement_unit' => $line->measurement_unit,
                'disabled' => $line->status === 'reported', // ✅ Mark as disabled if reported
            ];
        })->toArray();

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    protected function handleRecordUpdate($record, array $data): Model
    {
        $record->update($data);

        foreach ($data['daily_operations'] as $operation) {
            if (!empty($operation['disabled'])) {
                continue; // ✅ Skip lines that are reported
            }

            // Update or create operation line
            $line = $record->lines()->updateOrCreate(
                ['id' => $operation['id'] ?? null],
                [
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
                ]
            );

            $this->syncOrCreateRelations($line, 'assignedEmployees', 'user_id', $operation['employee_ids'] ?? []);
            $this->syncOrCreateRelations($line, 'assignedSupervisors', 'user_id', $operation['supervisor_ids'] ?? []);
            $this->syncOrCreateRelations($line, 'assignedProductionMachines', 'production_machine_id', $operation['machine_ids'] ?? []);
            $this->syncOrCreateRelations($line, 'assignedThirdPartyServices', 'third_party_service_id', $operation['third_party_service_ids'] ?? []);
        }

        return $record;
    }

    protected function syncOrCreateRelations($line, string $relationName, string $foreignKey, array $ids)
    {
        $line->$relationName()->whereNotIn($foreignKey, $ids)->delete();

        foreach ($ids as $id) {
            $line->$relationName()->firstOrCreate(
                [$foreignKey => $id],
                ['created_by' => auth()->id(), 'updated_by' => auth()->id()]
            );
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl();
    }
}
