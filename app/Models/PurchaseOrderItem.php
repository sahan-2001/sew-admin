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
                $model->purchaseOrder->recalculateGrandTotal();
            }
        });

        static::deleted(function ($model) {
            if ($model->purchaseOrder) {
                $model->purchaseOrder->recalculateGrandTotal();
            }
        });
    }
    
    // Helper method to calculate values
    public function calculateAndSetValues()
    {
        $this->item_subtotal = $this->quantity * $this->price;
        
        // Get VAT rate from related inventory item's VAT group
        $vatRate = 0;
        if ($this->inventoryItem && $this->inventoryItem->vatGroup) {
            $vatRate = $this->inventoryItem->vatGroup->vat_rate;
        } elseif ($this->inventory_item_vat_group_id) {
            // Try to get VAT rate from the stored ID
            $vatGroup = \App\Models\InventoryItemVatGroup::find($this->inventory_item_vat_group_id);
            if ($vatGroup) {
                $vatRate = $vatGroup->vat_rate;
            }
        }
        
        $this->item_vat_amount = ($this->item_subtotal * $vatRate) / 100;
        $this->item_grand_total = $this->item_subtotal + $this->item_vat_amount;
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