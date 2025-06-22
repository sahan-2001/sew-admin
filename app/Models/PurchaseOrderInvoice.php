<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class PurchaseOrderInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'register_arrival_id',
        'provider_type',
        'provider_id',
        'status',
        'grand_total',
        'adv_paid',
        'additional_cost',
        'discount',
        'due_payment',
        'due_payment_for_now',
        'created_by',
        'updated_by',
        'random_code'
    ];

    public function invoiceItems()
    {
        return $this->hasMany(PurchaseOrderInvoiceItem::class);
    }

    public function supplierAdvanceInvoices()
    {
        return $this->hasMany(SupplierAdvanceInvoice::class, 'purchase_order_id', 'purchase_order_id')
            ->whereIn('status', ['paid', 'partially_paid']);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function registerArrival()
    {
        return $this->belongsTo(RegisterArrival::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderInvoiceItem::class);
    }

    public function additionalCosts()
    {
        return $this->hasMany(PoAddCost::class);
    }

    public function discounts()
    {
        return $this->hasMany(PurchaseOrderDiscount::class);
    }

    public function advanceInvoiceDeductions()
    {
        return $this->hasMany(PoAdvInvDeduct::class);
    }

    public function payments()
    {
        return $this->hasMany(PoInvoicePayment::class);
    }

    protected static function booted()
    {
        static::creating(function ($invoice) {
            // Set created_by and updated_by
            $invoice->created_by = auth()->id();
            $invoice->updated_by = auth()->id();

            // Generate 16-digit random code
            $invoice->random_code = '';
            for ($i = 0; $i < 16; $i++) {
                $invoice->random_code .= mt_rand(0, 9);
            }

            // Set due_payment_for_now equal to due_payment if not set
            if (is_null($invoice->due_payment_for_now)) {
                $invoice->due_payment_for_now = $invoice->due_payment ?? 0;
            }
        });

        static::updating(function ($invoice) {
            // Update updated_by
            $invoice->updated_by = auth()->id();
        });
    }
}
