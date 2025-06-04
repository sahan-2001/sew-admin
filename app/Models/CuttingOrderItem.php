<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuttingOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cutting_record_id',
        'item_type',
        'item_id',
        'quantity',
        'start_label',
        'end_label',
        'created_by',
        'updated_by',
    ];

    public function cuttingRecord(): BelongsTo
    {
        return $this->belongsTo(CuttingRecord::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(CuttingOrderVariation::class, 'order_item_id');
    }

    public function labels(): HasMany
    {
        return $this->hasMany(CuttingLabel::class);
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