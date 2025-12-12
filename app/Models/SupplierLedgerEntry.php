<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierLedgerEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'entry_code',
        'supplier_id',
        'entry_date',
        'debit',
        'credit',
        'transaction_name',
        'description',
        'created_by',
        'updated_by',
    ];

    // Relations

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SupplierInvoice::class, 'invoice_id');
    }

    public function payment()
    {
        return $this->belongsTo(SupplierPayment::class, 'payment_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
