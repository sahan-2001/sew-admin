<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignedWorkingHour extends Model
{
    protected $fillable = [
        'assign_daily_operation_id',
        'operation_date',
        'start_time',
        'end_time',
        'created_by',
        'updated_by'
    ];

    public function assignDailyOperation()
    {
        return $this->belongsTo(AssignDailyOperation::class);
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