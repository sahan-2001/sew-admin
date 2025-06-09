<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialQC extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'material_qcs';

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'inspected_quantity',
        'approved_qty',
        'returned_qty',
        'scrapped_qty',
        'add_returned',
        'add_scrap',
        'total_returned',
        'total_scrap',
        'available_to_store',
        'cost_of_item',
        'store_location_id',
        'register_arrival_id',
        'inspected_by',
        'status',
        'created_by',
        'updated_by',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
    
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id'); 
    }

    public function inspectedBy()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function storeLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'store_location_id');
    }

    public function registerArrival()
    {
        return $this->belongsTo(RegisterArrival::class, 'register_arrival_id');
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