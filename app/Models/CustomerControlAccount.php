<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerControlAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_control_accounts';

    protected $fillable = [
        'customer_id',
        'receivable_account_id',
        'sales_account_id',
        'vat_output_account_id',
        'bad_debt_expense_account_id',
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

    protected $casts = [
        'debit_total' => 'decimal:2',
        'credit_total' => 'decimal:2',
        'balance' => 'decimal:2',
        'debit_total_vat' => 'decimal:2',
        'credit_total_vat' => 'decimal:2',
        'balance_vat' => 'decimal:2',
    ];


    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function receivableAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'receivable_account_id');
    }

    public function salesAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'sales_account_id');
    }

    public function vatOutputAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'vat_output_account_id');
    }

    public function badDebtExpenseAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'bad_debt_expense_account_id');
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


    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance, 2);
    }

    public function getFormattedBalanceVatAttribute(): string
    {
        return number_format($this->balance_vat, 2);
    }
}
