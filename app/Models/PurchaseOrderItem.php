<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseOrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'inventory_item_id',
        'quantity',
        'price',
        'arrived_quantity', 
        'remaining_quantity', 
        'total_sale',
        'created_by',
        'updated_by',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    protected static $logAttributes = [
        'purchase_order_id',
        'inventory_item_id',
        'quantity',
        'price',
        'arrived_quantity', 
        'remaining_quantity',
        'total_sale',
    ];

    protected static $logName = 'purchase_order_item';

    /**
     * Get the options for activity logging.
     *
     * @return \Spatie\Activitylog\LogOptions
     */

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });

        // Trigger grand total recalculation after change
        static::saved(function ($model) {
            $model->purchaseOrder->recalculateGrandTotal();
        });

        static::deleted(function ($model) {
            if ($model->purchaseOrder) {
                $model->purchaseOrder->recalculateGrandTotal();
            }
        });
    } 
}