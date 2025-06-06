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
}