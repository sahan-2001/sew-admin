<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinalProductQC extends Model
{
    use SoftDeletes;

    protected $table = 'final_product_qcs'; 

    protected $fillable = [
        'order_type',
        'order_id',
        'qc_officer_id',
        'inspected_date',
        'result',
        'created_by',
        'updated_by',
    ];

    public function qcLabels(): HasMany
    {
        return $this->hasMany(FinalProductQCLabel::class);
    }

    public function passedLabels(): HasMany
    {
        return $this->hasMany(FinalProductQCLabel::class)->where('result', 'passed');
    }

    public function failedLabels(): HasMany
    {
        return $this->hasMany(FinalProductQCLabel::class)->where('result', 'failed');
    }

    public function qcOfficer()
    {
        return $this->belongsTo(User::class, 'qc_officer_id');
    }

    public function scopeForOrder($query, $orderType, $orderId)
    {
        return $query->where('order_type', $orderType)
                    ->where('order_id', $orderId);
    }

    public function scopeWithStatuses($query, array $statuses)
    {
        return $query->whereIn('status', $statuses);
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