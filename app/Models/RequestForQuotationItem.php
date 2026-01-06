<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class RequestForQuotationItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'request_for_quotation_id',
        'inventory_item_id',
        'inventory_vat_group_id',
        'inventory_vat_rate',
        'quantity',
        'price',
        'item_subtotal',        
        'item_vat_amount',      
        'item_grand_total',     
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = Auth::id();
            $model->updated_by = Auth::id();
            $model->calculateAndSetValues();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
            $model->calculateAndSetValues();
        });

        static::saved(function ($model) {
            if ($model->requestForQuotation) {
                $model->requestForQuotation->recalculateTotals();
            }
        });

        static::deleted(function ($model) {
            if ($model->requestForQuotation) {
                $model->requestForQuotation->recalculateTotals();
            }
        });
    }

    // Helper method to calculate item values
    public function calculateAndSetValues()
    {
        $this->item_subtotal = $this->quantity * $this->price;

        if ($this->requestForQuotation?->vat_base === 'item_vat') {
            $vatRate = (float) ($this->inventory_vat_rate ?? 0);
            $this->item_vat_amount  = round(($this->item_subtotal * $vatRate) / 100, 2);
            $this->item_grand_total = round($this->item_subtotal + $this->item_vat_amount, 2);
        } else {
            $this->item_vat_amount  = 0;
            $this->item_grand_total = $this->item_subtotal;
        }
    }

    /* -----------------------
     | RELATIONSHIPS
     ----------------------- */
    public function requestForQuotation()
    {
        return $this->belongsTo(RequestForQuotation::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id'); 
    }
}
