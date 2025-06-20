<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssignDailyOperationLine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'assign_daily_operation_id',
        'production_line_id',
        'workstation_id',
        'operation_id',
        'machine_setup_time',
        'machine_run_time',
        'labor_setup_time',
        'labor_run_time',
        'target_duration',
        'target_e',
        'target_m',
        'measurement_unit',
        'status',
        'created_by',
        'updated_by',
    ];

    public function assignDailyOperation()
    {
        return $this->belongsTo(AssignDailyOperation::class, 'assign_daily_operation_id');
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function workstation()
    {
        return $this->belongsTo(Workstation::class);
    }

    public function operation()
    {
        return $this->belongsTo(Operation::class);
    }
    
    public function assignedEmployees()
    {
        return $this->hasMany(AssignedEmployee::class);
    }

    public function assignedSupervisors()
    {
        return $this->hasMany(AssignedSupervisor::class);
    }

    public function assignedProductionMachines()
    {
        return $this->hasMany(AssignedProductionMachine::class);
    }

    public function assignedThirdPartyServices()
    {
        return $this->hasMany(AssignedThirdPartyService::class);
    }

    public function employees()
    {
        return $this->belongsToMany(User::class, 'assign_daily_operation_line_employees', 'operation_line_id', 'employee_id');
    }

    public function supervisors()
    {
        return $this->belongsToMany(User::class, 'assign_daily_operation_line_supervisors', 'operation_line_id', 'supervisor_id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
}