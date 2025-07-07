<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdditionalOrderDiscount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_type',
        'order_id',
        'amount',
        'description',
        'recorded_date',
        'remarks',
        'status',
        'created_by',
        'updated_by',
    ];

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

    public function customerOrder()
    {
        return $this->belongsTo(CustomerOrder::class, 'order_id');
    }

    public function sampleOrder()
    {
        return $this->belongsTo(SampleOrder::class, 'order_id');
    }

}
