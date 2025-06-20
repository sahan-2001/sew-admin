<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PoAdvInvDeduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'po_adv_inv_deduct';
    
    protected $fillable = [
        'purchase_order_invoice_id',
        'advance_invoice_id',
        'deduction_amount',
        'created_by',
        'updated_by',
    ];

    public function invoice()
    {
        return $this->belongsTo(PurchaseOrderInvoice::class, 'purchase_order_invoice_id');
    }

    public function supplierAdvanceInvoices()
    {
        return $this->hasMany(SupplierAdvanceInvoice::class);
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
