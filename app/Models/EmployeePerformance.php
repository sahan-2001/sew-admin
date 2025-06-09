<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePerformance extends Model
{
    use HasFactory;

    protected $fillable = [
        'enter_performance_record_id',
        'employee_id',
        'production_quantity',
        'production_unit',
        'waste_quantity',
        'waste_unit',
        'working_hours',
        'efficiency',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function performanceRecord()
    {
        return $this->belongsTo(EnterPerformanceRecord::class, 'enter_performance_record_id');
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