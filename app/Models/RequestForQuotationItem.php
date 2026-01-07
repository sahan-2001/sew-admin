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
        'quantity',
        'price',
        'item_subtotal',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = Auth::id();
            $model->updated_by = Auth::id();
            $model->calculateSubtotal();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
            $model->calculateSubtotal();
        });

        static::saved(function ($model) {
            $model->requestForQuotation?->recalculateTotals();
        });

        static::deleted(function ($model) {
            $model->requestForQuotation?->recalculateTotals();
        });
    }

    /* -----------------------
     | HELPER
     ----------------------- */
    protected function calculateSubtotal(): void
    {
        $qty   = (float) ($this->quantity ?? 0);
        $price = (float) ($this->price ?? 0);

        $this->item_subtotal = round($qty * $price, 2);
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
        return $this->belongsTo(InventoryItem::class);
    }
}
