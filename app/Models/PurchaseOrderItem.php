<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseOrderItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'purchase_order_id',
        'inventory_item_id',
        'quantity',
        'price',
        'arrived_quantity', // New column
        'remaining_quantity', // New column
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
        'arrived_quantity', // New column
        'remaining_quantity', // New column
    ];

    protected static $logName = 'purchase_order_item';

    /**
     * Get the options for activity logging.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'purchase_order_id',
                'inventory_item_id',
                'quantity',
                'price',
                'arrived_quantity', // New column
                'remaining_quantity', // New column
            ])
            ->useLogName('purchase_order_item')
            ->setDescriptionForEvent(function (string $eventName) {
                $userEmail = $this->purchaseOrder->user ? $this->purchaseOrder->user->email : 'unknown';
                return "Purchase Order Item {$this->id} for Purchase Order {$this->purchase_order_id} has been {$eventName} by User {$this->purchaseOrder->user_id} ({$userEmail})";
            });
    }
}