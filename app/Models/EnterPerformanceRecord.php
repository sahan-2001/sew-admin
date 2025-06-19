<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class EnterPerformanceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assign_daily_operation_id',
        'assign_daily_operation_line_id',
        'operation_date',
        'operated_time_from',
        'operated_time_to',
        'actual_machine_setup_time',
        'actual_machine_run_time',
        'actual_employee_setup_time',
        'actual_employee_run_time',
        'status',
        'created_by',
        'updated_by',
    ];

    public function assignDailyOperation()
    {
        return $this->belongsTo(AssignDailyOperation::class, 'assign_daily_operation_id');
    }

    public function employeePerformances()
    {
        return $this->hasMany(EnterEmployeePerformance::class);
    }

    public function machinePerformances()
    {
        return $this->hasMany(EnterMachinePerformance::class);
    }

    public function supervisorPerformances()
    {
        return $this->hasMany(EnterSupervisorPerformance::class);
    }

    public function servicePerformances()
    {
        return $this->hasMany(EnterServicePerformance::class);
    }

    public function invWastePerformances()
    {
        return $this->hasMany(EnterInvWastePerformance::class);
    }

    public function nonInvPerformances()
    {
        return $this->hasMany(EnterNonInvPerformance::class);
    }

    public function byProductsPerformances()
    {
        return $this->hasMany(EnterByProductsPerformance::class);
    }

    public function qcPerformances()
    {
        return $this->hasMany(EnterQcPerformance::class);
    }

    public function employeeLabelPerformances()
    {
        return $this->hasMany(EnterEmployeeLabelPerformance::class);
    }

    public function machineLabelPerformances()
    {
        return $this->hasMany(EnterMachineLabelPerformance::class);
    }

    public function qcLabelPerformances()
    {
        return $this->hasMany(EnterQcLabelPerformance::class);
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
