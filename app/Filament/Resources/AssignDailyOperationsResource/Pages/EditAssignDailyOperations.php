<?php

namespace App\Filament\Resources\AssignDailyOperationsResource\Pages;

use App\Filament\Resources\AssignDailyOperationsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAssignDailyOperations extends EditRecord
{
    protected static string $resource = AssignDailyOperationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $data = $this->getRecord()->toArray();
        
        // Add order type and order ID to the form data
        $data['order_type'] = $this->getRecord()->order_type;
        $data['order_id'] = $this->getRecord()->order_id;
        $data['show_operation_date'] = !empty($data['working_hours']);
        
        // Load the related operation lines
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
                'supervisor_ids' => $line->assignedsupervisors->pluck('user_id')->toArray(),
                'machine_ids' => $line->assignedproductionmachines->pluck('production_machine_id')->toArray(),
                'third_party_service_ids' => $line->assignedthirdPartyServices->pluck('third_party_service_id')->toArray(),
                'setup_time' => $line->setup_time,
                'run_time' => $line->run_time,
                'target_durattion' => $line->target_duration,
                'target' => $line->target,
                'measurement_unit' => $line->measurement_unit,
            ];
        })->toArray();

        // Load the related working hours
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
}