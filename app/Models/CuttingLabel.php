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
        'status',
        'barcode',
        'barcode_id',
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

    public function getBarcodeUrlAttribute()
    {
        return $this->barcode ? asset($this->barcode) : null;
    }
        
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
            
            // Ensure barcode_id is set if not provided
            if (empty($model->barcode_id)) {
                $model->barcode_id = uniqid('CUT');
            }
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
}