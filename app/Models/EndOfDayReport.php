<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EndOfDayReport extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'operated_date',
        'recorded_operations_count',
        'created_by',
        'updated_by',
    ];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $logName = 'end_of_day_report';

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

    public function operations()
    {
        return $this->hasMany(EndOfDayReportOperation::class);
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('end_of_day_report')
            ->setDescriptionForEvent(function (string $eventName) {
                $changes = $this->getDirty();
                $description = "End of Day Report #{$this->id} was {$eventName}";

                if ($eventName === 'updated' && !empty($changes)) {
                    $description .= ". Changes: " . json_encode($changes);
                }

                $user = auth()->user();
                if ($user) {
                    $description .= " by {$user->name} (ID: {$user->id})";
                }

                return $description;
            });
    }
}
