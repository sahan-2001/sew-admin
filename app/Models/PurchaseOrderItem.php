<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class PurchaseOrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'inventory_item_id',
        'inventory_vat_group_id',
        'inventory_vat_rate',
        'quantity',
        'price',
        'item_subtotal',        
        'item_vat_amount',      
        'item_grand_total',     
        'arrived_quantity', 
        'remaining_quantity', 
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
            
            // Calculate values before saving
            $model->calculateAndSetValues();
            
            // Set remaining quantity equal to quantity
            $model->remaining_quantity = $model->quantity;
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
            $model->calculateAndSetValues();
        });

        // Trigger grand total recalculation after change
        static::saved(function ($model) {
            if ($model->purchaseOrder) {
                $model->purchaseOrder->recalculateTotals();
            }
        });

        static::deleted(function ($model) {
            if ($model->purchaseOrder) {
                $model->purchaseOrder->recalculateTotals();
            }
        });
    }
    
    // Helper method to calculate values
    public function calculateAndSetValues()
    {
        $this->item_subtotal = $this->quantity * $this->price;

        // Only calculate item VAT if order is item-based
        if ($this->purchaseOrder?->vat_base === 'item_vat') {
            $vatRate = (float) ($this->inventory_vat_rate ?? 0);
            $this->item_vat_amount  = round(($this->item_subtotal * $vatRate) / 100, 2);
            $this->item_grand_total = round($this->item_subtotal + $this->item_vat_amount, 2);
        } else {
            // Supplier VAT → no item VAT
            $this->item_vat_amount  = 0;
            $this->item_grand_total = $this->item_subtotal;
        }
    }
    
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); 
    }
}