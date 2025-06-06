<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class CuttingLabel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cutting_record_id',
        'order_type',
        'order_id',
        'order_item_id',
        'order_variation_id',
        'quantity',
        'label',
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

    public function variation(): BelongsTo
    {
        return $this->belongsTo(CuttingOrderVariation::class, 'order_variation_id');
    }

    public function assignedOperations(): BelongsToMany
    {
        return $this->belongsToMany(AssignDailyOperation::class, 'assign_daily_operation_labels')
            ->using(AssignDailyOperationLabel::class)
            ->withTimestamps()
            ->withPivot('id', 'deleted_at');
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