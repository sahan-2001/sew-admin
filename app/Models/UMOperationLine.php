<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\SoftDeletes;

class UMOperationLine extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'u_m_operation_id',
        'production_line_id',
        'workstation_id',
        'operation_id',
        'setup_time',
        'run_time',
        'target_duration',
        'target',
        'measurement_unit',
        'created_by',
        'updated_by',
    ];

    public function umOperation()
    {
        return $this->belongsTo(UMOperation::class, 'u_m_operation_id');
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
    
    public function uMOperationEmployees()
    {
        return $this->hasMany(UMOperationLineEmployee::class);
    }
    public function uMOperationSupervisors()
    {
        return $this->hasMany(UMOperationLineSupervisor::class);
    }
    public function uMOperationMachines()
    {
        return $this->hasMany(UMOperationLineMachine::class);
    }
    public function uMOperationServices()
    {
        return $this->hasMany(UMOperationLineService::class);
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