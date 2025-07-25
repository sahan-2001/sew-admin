<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class EnterMachinePerformance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'enter_performance_record_id',
        'machine_id',
        'machine_downtime',
        'machine_notes',
        'created_by',
        'updated_by',
    ];

    public function performanceRecord()
    {
        return $this->belongsTo(EnterPerformanceRecord::class);
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