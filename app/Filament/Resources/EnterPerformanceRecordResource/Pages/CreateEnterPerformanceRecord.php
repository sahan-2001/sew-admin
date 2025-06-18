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
use App\Models\EnterEmployeeLabelPerformance;
use App\Models\EnterMachineLabelPerformance;
use App\Models\EnterQcLabelPerformance;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateEnterPerformanceRecord extends CreateRecord
{
    protected static string $resource = EnterPerformanceRecordResource::class;

    protected function handleRecordCreation(array $data): EnterPerformanceRecord
    {
        return DB::transaction(function () use ($data) {
            // Save the main performance record
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

            

            return $performanceRecord;
        });
    }
}