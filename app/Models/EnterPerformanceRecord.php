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

    }
