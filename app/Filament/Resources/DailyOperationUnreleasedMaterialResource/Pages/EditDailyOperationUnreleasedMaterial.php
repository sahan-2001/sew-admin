<?php

namespace App\Filament\Resources\DailyOperationUnreleasedMaterialResource\Pages;

use App\Filament\Resources\DailyOperationUnreleasedMaterialResource;
use App\Models\UMOperation;
use App\Models\UMOperationLine;
use App\Models\UMOperationLineEmployee;
use App\Models\UMOperationLineSupervisor;
use App\Models\UMOperationLineMachine;
use App\Models\UMOperationLineService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDailyOperationUnreleasedMaterial extends EditRecord
{
    protected static string $resource = DailyOperationUnreleasedMaterialResource::class;

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
            'umOperationEmployees',
            'umOperationSupervisors',
            'umOperationMachines',
            'umOperationServices'
        ])->get()->map(function ($line) {
            return [
                'id' => $line->id,
                'production_line_id' => $line->production_line_id,
                'production_line_name' => $line->productionLine->name ?? 'N/A',
                'workstation_id' => $line->workstation_id,
                'workstation_name' => $line->workstation->name ?? 'N/A',
                'operation_id' => $line->operation_id,
                'operation_description' => $line->operation->description ?? 'N/A',
                'employee_ids' => $line->umOperationEmployees->pluck('user_id')->toArray(),
                'supervisor_ids' => $line->umOperationSupervisors->pluck('user_id')->toArray(),
                'machine_ids' => $line->umOperationMachines->pluck('production_machine_id')->toArray(),
                'third_party_service_ids' => $line->umOperationServices->pluck('third_party_service_id')->toArray(),
                'machine_setup_time' => $line->machine_setup_time,
                'machine_run_time' => $line->machine_run_time,
                'labor_setup_time' => $line->labor_setup_time,
                'labor_run_time' => $line->labor_run_time,
                'target_duration' => $line->target_duration,
                'target' => $line->target,
                'measurement_unit' => $line->measurement_unit,
            ];
        })->toArray();

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Update main record
        $record->update([
            'order_type' => $data['order_type'],
            'order_id' => $data['order_id'],
            'updated_by' => auth()->id(),
        ]);

        // Get existing line IDs to track which ones to keep
        $existingLineIds = $record->lines()->pluck('id')->toArray();
        $updatedLineIds = [];

        // Update or create operation lines
        foreach ($data['daily_operations'] as $operation) {
            $line = $record->lines()->updateOrCreate(
                ['id' => $operation['id'] ?? null],
                [
                    'production_line_id' => $operation['production_line_id'],
                    'workstation_id' => $operation['workstation_id'],
                    'operation_id' => $operation['operation_id'],
                    'machine_setup_time' => $operation['machine_setup_time'],
                    'labor_setup_time' => $operation['labor_setup_time'],
                    'machine_run_time' => $operation['machine_run_time'],
                    'labor_run_time' => $operation['labor_run_time'] ,
                    'target_duration' => $operation['target_duration'],
                    'target' => $operation['target'],
                    'measurement_unit' => $operation['measurement_unit'],
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]
            );

            $updatedLineIds[] = $line->id;

            // Sync related entities
            $this->syncOrCreateRelations($line, 'umOperationEmployees', 'user_id', $operation['employee_ids'] ?? []);
            $this->syncOrCreateRelations($line, 'umOperationSupervisors', 'user_id', $operation['supervisor_ids'] ?? []);
            $this->syncOrCreateRelations($line, 'umOperationMachines', 'production_machine_id', $operation['machine_ids'] ?? []);
            $this->syncOrCreateRelations($line, 'umOperationServices', 'third_party_service_id', $operation['third_party_service_ids'] ?? []);
        }

        // Delete lines that were removed
        $linesToDelete = array_diff($existingLineIds, $updatedLineIds);
        if (!empty($linesToDelete)) {
            $record->lines()->whereIn('id', $linesToDelete)->delete();
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