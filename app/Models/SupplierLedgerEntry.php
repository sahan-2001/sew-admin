<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierLedgerEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'site_id',
        'entry_code',
        'supplier_id',
        'chart_of_account_id',
        'vat_control_account_id',
        'cash_bank_control_account_id',
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

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
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

    public function vatControlAccount()
    {
        return $this->belongsTo(ControlAccount::class, 'vat_control_account_id');
    }

    public function cashBankControlAccount()
    {
        return $this->belongsTo(ControlAccount::class, 'cash_bank_control_account_id');
    }

     /**
     * Automatically assign created_by and updated_by
     */ 

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
