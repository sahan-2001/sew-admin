<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EndOfDayReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'summary',
        'total_output',
        'total_waste',
    ];

    public function assign_daily_operation()
    {
        return $this->belongsTo(AssignDailyOperation::class, 'assign_daily_operation_id');
    }

    public function assign_daily_operation_line()
    {
        return $this->belongsTo(AssignDailyOperationLine::class, 'assign_daily_operation_line_id');
    }

    public function enterPerformanceRecord()
    {
        return $this->belongsTo(EnterPerformanceRecord::class, 'enter_performance_record_id');
    }

}
