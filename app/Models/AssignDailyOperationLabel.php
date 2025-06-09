<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssignDailyOperationLabel extends Pivot
{
    use SoftDeletes;

    protected $table = 'assign_daily_operation_labels';

    protected $fillable = [
        'assign_daily_operation_id',
        'cutting_label_id',
    ];

    public $incrementing = true; 

    public function cuttingLabel()
    {
        return $this->belongsTo(\App\Models\CuttingLabel::class, 'cutting_label_id');
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