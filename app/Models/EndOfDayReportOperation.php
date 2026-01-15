<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EndOfDayReportOperation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'site_id',
        'end_of_day_report_id',
        'enter_performance_record_id',
        'assign_daily_operation_id',
        'operation_line_id',
        'temporary_operation_id',
        'created_by',
        'updated_by'
    ];
    
    public function report()
    {
        return $this->belongsTo(EndOfDayReport::class, 'end_of_day_report_id');
    }

    public function performanceRecord()
    {
        return $this->belongsTo(EnterPerformanceRecord::class, 'enter_performance_record_id');
    }

    public function assignedOperation()
    {
        return $this->belongsTo(AssignDailyOperation::class, 'assign_daily_operation_id');
    }

    public function temporaryOperation()
    {
        return $this->belongsTo(TemporaryOperation::class, 'temporary_operation_id');
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

}
