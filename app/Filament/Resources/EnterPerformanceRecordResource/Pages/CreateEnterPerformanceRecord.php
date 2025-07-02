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
use Filament\Resources\Pages\CreateRecord;

class CreateEnterPerformanceRecord extends CreateRecord
{
    protected static string $resource = EnterPerformanceRecordResource::class;

    protected function handleRecordCreation(array $data): EnterPerformanceRecord
    {
        // Save the main record
        $performanceRecord = EnterPerformanceRecord::create([
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

        // Save employee performances
        if (!empty($data['employee_details'])) {
            foreach ($this->data['employee_details'] as $emp) {
                        EnterEmployeePerformance::create([
                            'enter_performance_record_id' => $performanceRecord->id,
                            'employee_id' => $emp['employee_id'], 
                            'emp_production' => count($emp['selected_labels_e'] ?? []),
                            'emp_downtime' => $emp['emp_downtime'] ?? 0,
                        ]);

                // Save selected labels for the employee
                if (!empty($emp['selected_labels_e']) && is_array($emp['selected_labels_e'])) {
                    foreach ($emp['selected_labels_e'] as $labelId) {
                        EnterEmployeeLabelPerformance::create([
                            'enter_performance_record_id' => $performanceRecord->id,
                            'cutting_label_id' => $labelId,
                            'employee_id' => $emp['employee_id'],
                        ]);
                    }
                }
            }
        }

        // Save machine performances
        if (!empty($data['machines'])) {
            foreach ($this->data['machines'] as $machine) {
                // Save machine performance
                EnterMachinePerformance::create([
                    'enter_performance_record_id' => $performanceRecord->id,
                    'machine_id' => $machine['machine_id'],
                    'machine_downtime' => $machine['machine_downtime'] ?? 0,
                    'machine_notes' => $machine['machine_notes'] ?? null,
                ]);

                // Save selected labels for the machine
                if (!empty($machine['selected_labels_m']) && is_array($machine['selected_labels_m'])) {
                    foreach ($machine['selected_labels_m'] as $labelId) {
                        EnterMachineLabelPerformance::create([
                            'enter_performance_record_id' => $performanceRecord->id,
                            'machine_id' => $machine['machine_id'],
                            'cutting_label_id' => $labelId,
                        ]);
                    }
                }
            }
        }

        // Save supervisor performances
        if (!empty($data['supervisor_details'])) {
            foreach ($this->data['supervisor_details'] as $supervisor) {
                // Save supervisor performance
                EnterSupervisorPerformance::create([
                    'enter_performance_record_id' => $performanceRecord->id,
                    'supervisor_id' => $supervisor['supervisor_id'],
                    'accepted_qty' => $supervisor['acc_quantity'] ,
                    'rejected_qty' => $supervisor['rej_quantity'] ,
                    'supervisored_qty' => $supervisor['sup_quantity'],
                    'sup_downtime' => $supervisor['sup_downtime'],
                    'sup_notes' => $supervisor['sup_notes'] ?? null,
                ]);
            }
        }

        // Save third-party performances
        if (!empty($data['services'])) {
            foreach ($this->data['services'] as $service) {
                if (!empty($service['processes'])) {
                    foreach ($service['processes'] as $process) {
                        // Save service performance
                        EnterServicePerformance::create([
                            'enter_performance_record_id' => $performanceRecord->id,
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

        // Save Inventory Waste Products
        if (!empty($data['inv_waste_products']) && count($data['inv_waste_products']) > 0) {
            foreach ($data['inv_waste_products'] as $wasteProduct) {
                if (!empty($wasteProduct['waste']) && !empty($wasteProduct['waste_measurement_unit']) && !empty($wasteProduct['waste_item_id']) && !empty($wasteProduct['waste_location_id'])) {
                    EnterInvWastePerformance::create([
                        'enter_performance_record_id' => $performanceRecord->id,
                        'waste' => $wasteProduct['waste'],
                        'uom' => $wasteProduct['waste_measurement_unit'],
                        'item_id' => $wasteProduct['waste_item_id'],
                        'location_id' => $wasteProduct['waste_location_id'],
                    ]);
                }
            }
        }

        // Save Non-Inventory Waste Products
        if (!empty($data['non_inv_waste_products']) && count($data['non_inv_waste_products']) > 0) {
            foreach ($data['non_inv_waste_products'] as $nonInvWasteProduct) {
                if (!empty($nonInvWasteProduct['amount']) && !empty($nonInvWasteProduct['unit']) && !empty($nonInvWasteProduct['item_id'])) {
                    EnterNonInvPerformance::create([
                        'enter_performance_record_id' => $performanceRecord->id,
                        'amount' => $nonInvWasteProduct['amount'],
                        'uom' => $nonInvWasteProduct['unit'],
                        'item_id' => $nonInvWasteProduct['item_id'],
                    ]);
                }
            }
        }

        // Save By Products
        if (!empty($data['by_products']) && count($data['by_products']) > 0) {
            foreach ($data['by_products'] as $byProduct) {
                if (!empty($byProduct['amount']) && !empty($byProduct['measurement_unit']) && !empty($byProduct['item_id']) && !empty($byProduct['by_location_id'])) {
                    EnterByProductsPerformance::create([
                        'enter_performance_record_id' => $performanceRecord->id,
                        'amount' => $byProduct['amount'],
                        'uom' => $byProduct['measurement_unit'],
                        'item_id' => $byProduct['item_id'],
                        'location_id' => $byProduct['by_location_id'],
                    ]);
                }
            }
        }

        // Handle QC Performance - FIXED VERSION
        $passedLabels = $data['selected_labels_qc_p'] ?? [];
        $failedLabels = $data['selected_labels_qc_f'] ?? [];
        $hasQcData = !empty($passedLabels) || !empty($failedLabels);

        if ($hasQcData) {
            // Save QC labels for passed items
            if (!empty($passedLabels)) {
                foreach ($passedLabels as $labelId) {
                    EnterQcLabelPerformance::create([
                        'enter_performance_record_id' => $performanceRecord->id,
                        'cutting_label_id' => $labelId,
                        'result' => 'passed',
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }
            }

            // Save QC labels for failed items
            if (!empty($failedLabels)) {
                foreach ($failedLabels as $labelId) {
                    EnterQcLabelPerformance::create([
                        'enter_performance_record_id' => $performanceRecord->id,
                        'cutting_label_id' => $labelId,
                        'result' => 'failed',
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                }
            }

            // Save QC Performance record (always save when there's QC data)
            EnterQcPerformance::create([
                'enter_performance_record_id' => $performanceRecord->id,
                'action_type' => $data['failed_item_action'] ?? null,
                'cutting_station_id' => $data['cutting_station_id'] ?? null,
                'assign_operation_line_id' => $data['sawing_operation_id'] ?? null,
                'no_of_passed_items' => count($passedLabels),
                'no_of_failed_items' => count($failedLabels),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }


        return $performanceRecord;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
   

}