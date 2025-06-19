<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class EnterEmployeePerformance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'enter_performance_record_id',
        'employee_id',
        'emp_production',
        'emp_downtime',
        'created_by',
        'updated_by',
    ];

    public function performanceRecord()
    {
        return $this->belongsTo(EnterPerformanceRecord::class, 'enter_performance_record_id');
    }

    public function employeeLabelPerformance()
    {
        return $this->hasMany(EnterEmployeeLabelPerformance::class, 'enter_employee_performance_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
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