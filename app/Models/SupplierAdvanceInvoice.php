<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierAdvanceInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'status',
        'grand_total',
        'payment_type',
        'fix_payment_amount',
        'payment_percentage',
        'percent_calculated_payment',
        'created_by',
        'updated_by',
        'paid_amount',
        'remaining_amount',
        'paid_date',
        'paid_via',
        'random_code', 
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrderAdvInvDeduction()
    {
        return $this->belongsTo(PurchaseOrderAdvInvDeduction::class);
    }

    protected static function booted()
    {
        static::creating(function ($invoice) {
            // Set created_by and updated_by
            $invoice->created_by = auth()->id();
            $invoice->updated_by = auth()->id();

            // Set random 16-digit code
            $invoice->random_code = '';
            for ($i = 0; $i < 16; $i++) {
                $invoice->random_code .= mt_rand(0, 9);
            }

            // Calculate remaining amount
            if ($invoice->fix_payment_amount) {
                $invoice->remaining_amount = $invoice->fix_payment_amount;
            } elseif ($invoice->percent_calculated_payment) {
                $invoice->remaining_amount = $invoice->percent_calculated_payment;
            } else {
                $invoice->remaining_amount = 0;
            }
        });

        static::updating(function ($invoice) {
            $invoice->updated_by = auth()->id();
        });
    }
}
