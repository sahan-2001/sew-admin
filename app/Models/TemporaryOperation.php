<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemporaryOperation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_type',
        'order_id',
        'customer_id',
        'wanted_date',
        'description',
        'production_line_id',
        'workstation_id',
        'setup_time',
        'run_time',
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
}