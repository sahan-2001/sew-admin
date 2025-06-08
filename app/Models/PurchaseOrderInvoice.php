<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderInvoice extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'invoice_number',
        'invoice_date',
        'amount',
        'remarks',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
