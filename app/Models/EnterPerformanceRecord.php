<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnterPerformanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_type',
        'order_id',
        'performance_records',
    ];

    protected $casts = [
        'performance_records' => 'array',
    ];

    public function assignedOperations()
    {
        return $this->hasMany(AssignedDailyOperationLines::class);
    }

    public function temporaryOperation()
    {
        return $this->belongsTo(TemporaryOperation::class);
    }

    public function umOperation()
    {
        return $this->belongsTo(UMOperation::class);
    }

    public function assigned_employees()
    {
        return $this->hasMany(\App\Models\AssignedEmployee::class, 'assign_daily_operation_line_id');
    }

    public function employeePerformances()
    {
        return $this->hasMany(EmployeePerformance::class);
    }

    public function labels()
    {
        return $this->hasManyThrough(
            \App\Models\CuttingLabel::class,
            \App\Models\AssignDailyOperationLabel::class,
            'assign_daily_operation_id', 
            'id',
            'id', 
            'cutting_label_id' 
        );
    }

}
