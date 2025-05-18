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
}
