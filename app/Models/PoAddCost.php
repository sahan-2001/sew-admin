<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoAddCost extends Model
{
    protected $fillable = [
        'site_id',
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

    protected static function booted()
    {
        static::creating(function ($model) {
            // Set site_id from session
            if (session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
}