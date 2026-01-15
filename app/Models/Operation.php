<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Operation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'site_id',
        'workstation_id',
        'description',
        'status',
        'employee_id',
        'supervisor_id',
        'third_party_service_id',
        'machine_id',
        'machine_setup_time',
        'machine_run_time',
        'labor_setup_time',
        'labor_run_time',
        'created_by',
        'updated_by',
    ];

    public function workstation()
    {
        return $this->belongsTo(Workstation::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function thirdPartyService()
    {
        return $this->belongsTo(ThirdPartyService::class);
    }

    public function machine()
    {
        return $this->belongsTo(ProductionMachine::class);
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
            ->logOnly([
                'workstation_id',
                'description',
                'status',
                'employee_id',
                'supervisor_id',
                'third_party_service_id',
                'machine_id',
                'machine_setup_time',
                'machine_run_time',
                'labor_setup_time',
                'labor_run_time',
                'created_by',
                'updated_by',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('operation')
            ->setDescriptionForEvent(function (string $eventName) {
                $workstationId = $this->workstation_id ?? 'N/A';
                $user = auth()->user();
                $userInfo = $user ? " by {$user->name} (ID: {$user->id})" : "";

                return "Operation #{$this->id} (Workstation ID: {$workstationId}) has been {$eventName}{$userInfo}";
            });
    }
}
