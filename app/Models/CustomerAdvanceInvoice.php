<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAdvanceInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_type',
        'order_id',
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
        'cus_invoice_number',
        'invoice_image',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
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

        parent::boot();

        static::creating(function ($invoice) {
            if ($invoice->fix_payment_amount) {
                $invoice->remaining_amount = $invoice->fix_payment_amount;
            } elseif ($invoice->percent_calculated_payment) {
                $invoice->remaining_amount = $invoice->percent_calculated_payment;
            } else {
                $invoice->remaining_amount = 0; 
            }
        });
    }
}