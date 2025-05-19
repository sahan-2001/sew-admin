<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Operation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
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
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
}