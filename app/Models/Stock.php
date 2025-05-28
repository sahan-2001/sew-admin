<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'quantity',
        'cost',
        'location_id',
        'purchase_order_id', 
    ];


    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    public function location()
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($stock) {
            $stock->updateItemAvailableQuantity();
        });

        static::deleted(function ($stock) {
            $stock->updateItemAvailableQuantity();
        });
    }

    public function updateItemAvailableQuantity()
    {
        $item = $this->item; 
        if ($item) {
            $sum = self::where('item_id', $item->id)->sum('quantity');

            $item->available_quantity = $sum;
            $item->saveQuietly();
        }
    }

}