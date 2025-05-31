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
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function performanceRecord()
    {
        return $this->belongsTo(EnterPerformanceRecord::class, 'enter_performance_record_id');
    }
}