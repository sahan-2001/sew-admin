<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TemporaryOperation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'order_type',
        'order_id',
        'customer_id',
        'wanted_date',
        'description',
        'production_line_id',
        'workstation_id',
        'operation_date',
        'machine_setup_time',
        'machine_run_time',
        'labor_setup_time',
        'labor_run_time',
        'status',
        'created_by',
        'updated_by',
    ];

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function workstation()
    {
        return $this->belongsTo(Workstation::class);
    }

    public function employees()
    {
        return $this->belongsToMany(User::class, 'temporary_operation_employees');
    }

    public function supervisors()
    {
        return $this->belongsToMany(User::class, 'temporary_operation_supervisors');
    }

    public function temporaryOperationEmployees()
    {
        return $this->hasMany(TemporaryOperationEmployee::class, 'temporary_operation_id');
    }

    public function temporaryOperationSupervisors()
    {
        return $this->hasMany(TemporaryOperationSupervisor::class, 'temporary_operation_id');
    }

    public function productionMachines()
    {
        return $this->belongsToMany(ProductionMachine::class, 'temporary_operation_production_machines');
    }

    public function services()
    {
        return $this->belongsToMany(ThirdPartyService::class, 'temporary_operation_services');
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('temporary_operation')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = auth()->user();
                $userInfo = $user ? " by {$user->name} (ID: {$user->id})" : "";
                return "TemporaryOperation #{$this->id} was {$eventName}{$userInfo}";
            });
    }
}
