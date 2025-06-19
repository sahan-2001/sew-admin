<?php

namespace App\Filament\Resources\EnterPerformanceRecordResource\Pages;

use App\Filament\Resources\EnterPerformanceRecordResource;
use App\Models\EnterPerformanceRecord;
use App\Models\EnterEmployeePerformance;
use App\Models\EnterMachinePerformance;
use App\Models\EnterSupervisorPerformance;
use App\Models\EnterServicePerformance;
use App\Models\EnterInvWastePerformance;
use App\Models\EnterNonInvPerformance;
use App\Models\EnterByProductsPerformance;
use App\Models\EnterQcPerformance;
use App\Models\EnterQcLabelPerformance;
use App\Models\EnterMachineLabelPerformance;
use App\Models\EnterEmployeeLabelPerformance;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditEnterPerformanceRecord extends EditRecord
{
    protected static string $resource = EnterPerformanceRecordResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // Update the main record
            $record->update([
                'assign_daily_operation_id' => $data['model_id'],
                'assign_daily_operation_line_id' => $data['operation_id'],
                'operation_date' => $data['operated_date'],
                'operated_time_from' => $data['operated_time_from'],
                'operated_time_to' => $data['operated_time_to'],
                'actual_machine_setup_time' => $data['actual_machine_setup_time'],
                'actual_machine_run_time' => $data['actual_machine_run_time'],
                'actual_employee_setup_time' => $data['actual_labor_setup_time'],
                'actual_employee_run_time' => $data['actual_labor_run_time'],
            ]);

            // Handle employee performances (delete old and create new)
            $this->handleEmployeePerformances($record, $data);
            
            // Handle machine performances
            $this->handleMachinePerformances($record, $data);
            
            // Handle supervisor performances
            $this->handleSupervisorPerformances($record, $data);
            
            // Handle service performances
            $this->handleServicePerformances($record, $data);
            
            // Handle inventory waste products
            $this->handleInventoryWaste($record, $data);
            
            // Handle non-inventory waste products
            $this->handleNonInventoryWaste($record, $data);
            
            // Handle by-products
            $this->handleByProducts($record, $data);
            
            // Handle QC data
            $this->handleQcData($record, $data);

            return $record;
        });
    }

    protected function handleEmployeePerformances($record, $data): void
    {
        // Delete existing employee performances and labels
        EnterEmployeePerformance::where('enter_performance_record_id', $record->id)->delete();
        EnterEmployeeLabelPerformance::where('enter_performance_record_id', $record->id)->delete();

        if (!empty($data['employee_details'])) {
            foreach ($data['employee_details'] as $emp) {
                // Create employee performance
                EnterEmployeePerformance::create([
                    'enter_performance_record_id' => $record->id,
                    'employee_id' => $emp['employee_id'], 
                    'emp_production' => count($data['selected_labels_e'] ?? []),
                    'emp_downtime' => $emp['emp_downtime'] ?? 0,
                ]);

                // Create employee labels
                if (!empty($emp['selected_labels_e']) && is_array($emp['selected_labels_e'])) {
                    foreach ($emp['selected_labels_e'] as $labelId) {
                        EnterEmployeeLabelPerformance::create([
                            'enter_performance_record_id' => $record->id,
                            'cutting_label_id' => $labelId,
                            'employee_id' => $emp['employee_id'],
                        ]);
                    }
                }
            }
        }
    }

    protected function handleMachinePerformances($record, $data): void
    {
        // Delete existing machine performances and labels
        EnterMachinePerformance::where('enter_performance_record_id', $record->id)->delete();
        EnterMachineLabelPerformance::where('enter_performance_record_id', $record->id)->delete();

        if (!empty($data['machines'])) {
            foreach ($data['machines'] as $machine) {
                // Create machine performance
                EnterMachinePerformance::create([
                    'enter_performance_record_id' => $record->id,
                    'machine_id' => $machine['machine_id'],
                    'machine_downtime' => $machine['machine_downtime'] ?? 0,
                    'machine_notes' => $machine['machine_notes'] ?? null,
                ]);

                // Create machine labels
                if (!empty($machine['selected_labels_m']) && is_array($machine['selected_labels_m'])) {
                    foreach ($machine['selected_labels_m'] as $labelId) {
                        EnterMachineLabelPerformance::create([
                            'enter_performance_record_id' => $record->id,
                            'machine_id' => $machine['machine_id'],
                            'cutting_label_id' => $labelId,
                        ]);
                    }
                }
            }
        }
    }

    protected function handleSupervisorPerformances($record, $data): void
    {
        // Delete existing supervisor performances
        EnterSupervisorPerformance::where('enter_performance_record_id', $record->id)->delete();

        if (!empty($data['supervisor_details'])) {
            foreach ($data['supervisor_details'] as $supervisor) {
                EnterSupervisorPerformance::create([
                    'enter_performance_record_id' => $record->id,
                    'supervisor_id' => $supervisor['supervisor_id'],
                    'accepted_qty' => $supervisor['acc_quantity'],
                    'rejected_qty' => $supervisor['rej_quantity'],
                    'supervisored_qty' => $supervisor['sup_quantity'],
                    'sup_downtime' => $supervisor['sup_downtime'],
                    'sup_notes' => $supervisor['sup_notes'] ?? null,
                ]);
            }
        }
    }

    protected function handleServicePerformances($record, $data): void
    {
        // Delete existing service performances
        EnterServicePerformance::where('enter_performance_record_id', $record->id)->delete();

        if (!empty($data['services'])) {
            foreach ($data['services'] as $service) {
                if (!empty($service['processes'])) {
                    foreach ($service['processes'] as $process) {
                        EnterServicePerformance::create([
                            'enter_performance_record_id' => $record->id,
                            'service_id' => $service['id'],
                            'service_process_id' => $process['process_id'],
                            'used_amount' => $process['used_amount'],
                            'unit_rate' => $process['unit_rate'],
                            'total_cost' => $process['total'],
                        ]);
                    }
                }
            }
        }
    }

    protected function handleInventoryWaste($record, $data): void
    {
        // Delete existing inventory waste records
        EnterInvWastePerformance::where('enter_performance_record_id', $record->id)->delete();

        if (!empty($data['inv_waste_products']) && count($data['inv_waste_products']) > 0) {
            foreach ($data['inv_waste_products'] as $wasteProduct) {
                if (!empty($wasteProduct['waste']) && !empty($wasteProduct['waste_measurement_unit']) && 
                    !empty($wasteProduct['waste_item_id']) && !empty($wasteProduct['waste_location_id'])) {
                    EnterInvWastePerformance::create([
                        'enter_performance_record_id' => $record->id,
                        'waste' => $wasteProduct['waste'],
                        'uom' => $wasteProduct['waste_measurement_unit'],
                        'item_id' => $wasteProduct['waste_item_id'],
                        'location_id' => $wasteProduct['waste_location_id'],
                    ]);
                }
            }
        }
    }

    protected function handleNonInventoryWaste($record, $data): void
    {
        // Delete existing non-inventory waste records
        EnterNonInvPerformance::where('enter_performance_record_id', $record->id)->delete();

        if (!empty($data['non_inv_waste_products']) && count($data['non_inv_waste_products']) > 0) {
            foreach ($data['non_inv_waste_products'] as $nonInvWasteProduct) {
                if (!empty($nonInvWasteProduct['amount']) && !empty($nonInvWasteProduct['unit']) && 
                    !empty($nonInvWasteProduct['item_id'])) {
                    EnterNonInvPerformance::create([
                        'enter_performance_record_id' => $record->id,
                        'amount' => $nonInvWasteProduct['amount'],
                        'uom' => $nonInvWasteProduct['unit'],
                        'item_id' => $nonInvWasteProduct['item_id'],
                    ]);
                }
            }
        }
    }

    protected function handleByProducts($record, $data): void
    {
        // Delete existing by-products records
        EnterByProductsPerformance::where('enter_performance_record_id', $record->id)->delete();

        if (!empty($data['by_products']) && count($data['by_products']) > 0) {
            foreach ($data['by_products'] as $byProduct) {
                if (!empty($byProduct['amount']) && !empty($byProduct['measurement_unit']) && 
                    !empty($byProduct['item_id']) && !empty($byProduct['by_location_id'])) {
                    EnterByProductsPerformance::create([
                        'enter_performance_record_id' => $record->id,
                        'amount' => $byProduct['amount'],
                        'uom' => $byProduct['measurement_unit'],
                        'item_id' => $byProduct['item_id'],
                        'location_id' => $byProduct['by_location_id'],
                    ]);
                }
            }
        }
    }

    protected function handleQcData($record, $data): void
    {
        // Delete existing QC records
        EnterQcPerformance::where('enter_performance_record_id', $record->id)->delete();
        EnterQcLabelPerformance::where('enter_performance_record_id', $record->id)->delete();

        // Handle passed QC labels
        if (!empty($data['selected_labels_qc_p'])) {
            foreach ($data['selected_labels_qc_p'] as $labelId) {
                EnterQcLabelPerformance::create([
                    'enter_performance_record_id' => $record->id,
                    'cutting_label_id' => $labelId,
                    'result' => 'passed',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        }

        // Handle failed QC labels
        if (!empty($data['selected_labels_qc_f'])) {
            foreach ($data['selected_labels_qc_f'] as $labelId) {
                EnterQcLabelPerformance::create([
                    'enter_performance_record_id' => $record->id,
                    'cutting_label_id' => $labelId,
                    'result' => 'failed',
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }
        }

        // Create QC performance record
        if (!empty($data['failed_item_action'])) {
            EnterQcPerformance::create([
                'enter_performance_record_id' => $record->id,
                'action_type' => $data['failed_item_action'],
                'cutting_station_id' => $data['cutting_station_id'] ?? null,
                'assign_operation_line_id' => $data['sawing_operation_id'] ?? null,
                'no_of_passed_items' => count($data['selected_labels_qc_p'] ?? []),
                'no_of_failed_items' => count($data['selected_labels_qc_f'] ?? []),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }
    }
}