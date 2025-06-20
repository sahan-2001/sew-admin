<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoAddCost extends Model
{
    protected $fillable = [
        'purchase_order_invoice_id',
        'description',
        'unit_rate',
        'quantity',
        'uom',
        'total',
        'date',
        'remarks'
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderInvoice::class);
    }
}