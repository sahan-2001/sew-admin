<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'site_id',
        'purchase_order_id',
        'inventory_item_id',
        'inventory_vat_group_id',
        'inventory_vat_rate',

        'quantity',
        'price',

        'item_subtotal',      // qty * price (BEFORE discount)
        'line_discount',      // flat amount
        'item_vat_amount',    // VAT AFTER discount
        'item_grand_total',   // discounted + VAT

        'arrived_quantity',
        'remaining_quantity',

        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {

            if (session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            $model->created_by = Auth::id() ?? 1;
            $model->updated_by = Auth::id() ?? 1;

            $model->line_discount ??= 0;
            $model->arrived_quantity ??= 0;

            if (isset($model->quantity)) {
                $model->remaining_quantity = $model->quantity;
            }
            $model->calculateAndSetValues();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id() ?? $model->updated_by;
            $model->calculateAndSetValues();
        });

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

    /* ---------------------------------
     | CORE CALCULATION LOGIC
     |---------------------------------*/
    public function calculateAndSetValues(): void
    {
        $qty       = (float) ($this->quantity ?? 0);
        $price     = (float) ($this->price ?? 0);
        $discount  = (float) ($this->line_discount ?? 0);
        $vatRate   = (float) ($this->inventory_vat_rate ?? 0);

        $subTotal = round($qty * $price, 2);

        $discountedSubTotal = max($subTotal - $discount, 0);

        if ($this->purchaseOrder?->vat_base === 'item_vat') {
            $vatAmount = round(($discountedSubTotal * $vatRate) / 100, 2);
        } else {
            $vatAmount = 0;
        }

        $grandTotal = round($discountedSubTotal + $vatAmount, 2);

        $this->item_subtotal    = $subTotal;
        $this->item_vat_amount  = $vatAmount;
        $this->item_grand_total = $grandTotal;
    }

    /* ---------------------------------
     | Relationships
     |---------------------------------*/
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
