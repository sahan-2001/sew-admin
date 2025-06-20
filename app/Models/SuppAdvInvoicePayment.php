<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class SuppAdvInvoicePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_advance_invoice_id',
        'payment_amount',
        'remaining_amount_before',
        'remaining_amount_after',
        'payment_method',
        'payment_reference',
        'notes',
        'paid_by',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'payment_amount' => 'decimal:2',
        'remaining_amount_before' => 'decimal:2',
        'remaining_amount_after' => 'decimal:2',
    ];

    public function supplierAdvanceInvoice()
    {
        return $this->belongsTo(SupplierAdvanceInvoice::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'paid_by');
    }

    protected static function booted()
    {
        static::creating(function ($payment) {
            $payment->paid_by = auth()->id();
            $payment->paid_at = now();
        });
    }
}