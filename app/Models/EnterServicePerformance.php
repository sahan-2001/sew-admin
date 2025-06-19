<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class EnterServicePerformance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'enter_performance_record_id',
        'service_id',
        'service_process_id',
        'used_amount',
        'unit_rate',
        'total_cost',
        'created_by',
        'updated_by',
    ];

    public function processes()
    {
        return $this->hasMany(ThirdPartyServiceProcess::class, 'third_party_service_id',);
    }

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