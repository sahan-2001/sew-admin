<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuttingOrderVariation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'site_id',
        'cutting_record_id',
        'order_item_id',
        'variation_type',
        'variation_id',
        'quantity',
        'created_by',
        'updated_by',
    ];

    public function cuttingRecord(): BelongsTo
    {
        return $this->belongsTo(CuttingRecord::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(CuttingOrderItem::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(CuttingLabel::class);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // Set site_id from session
            if (session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
}