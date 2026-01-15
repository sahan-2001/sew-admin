<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AssignDailyOperationLine extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'site_id',
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

    protected static $logAttributes = [
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

    protected static $logName = 'assign_daily_operation_line';

    // Log only changed attributes
    protected static $logOnlyDirty = true;

    // Don't log if no attributes changed
    protected static $submitEmptyLogs = false;

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
        return $this->belongsToMany(User::class, 'assign_daily_operation_line_employees', 'operation_line_id', 'employee_id')
            ->withTimestamps();
    }

    public function supervisors()
    {
        return $this->belongsToMany(User::class, 'assign_daily_operation_line_supervisors', 'operation_line_id', 'supervisor_id')
            ->withTimestamps();
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // Set site_id from session
            if (session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(static::$logAttributes)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(static::$logName)
            ->setDescriptionForEvent(function(string $eventName) {
                $user = auth()->user();
                $userName = $user ? $user->name : 'system';
                
                $description = "Daily Operation Line #{$this->id} (Operation: {$this->operation_id}) was {$eventName}";
                
                // Add more context if it's an update
                if ($eventName === 'updated') {
                    $changes = $this->getDirty();
                    if (isset($changes['status'])) {
                        $description .= ". Status changed to: {$changes['status']}";
                    }
                }
                
                return $description;
            });
    }
}