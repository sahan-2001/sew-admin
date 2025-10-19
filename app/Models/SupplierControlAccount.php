<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierControlAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'payable_account_id',
        'purchase_account_id',
        'vat_input_account_id',
        'purchase_discount_account_id',
        'bad_debt_recovery_account_id',
        'debit_total',
        'credit_total',
        'balance',
        'debit_total_vat',
        'credit_total_vat',
        'balance_vat',
        'status',
        'created_by',
        'updated_by',
    ];


    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function payableAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'payable_account_id');
    }

    public function purchaseAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'purchase_account_id');
    }

    public function vatInputAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'vat_input_account_id');
    }

    public function purchaseDiscountAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'purchase_discount_account_id');
    }

    public function badDebtRecoveryAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'bad_debt_recovery_account_id');
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
