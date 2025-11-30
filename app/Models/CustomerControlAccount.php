<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Customer;
use App\Models\ChartOfAccount;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerControlAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_control_accounts';

    protected $fillable = [
        'customer_id',

        // Account mappings
        'receivable_account_id',
        'sales_account_id',
        'export_sales_account_id',
        'sales_return_account_id',
        'sales_discount_account_id',
        'customer_advance_account_id',
        'bad_debt_expense_account_id',
        'allowance_for_doubtful_account_id',
        'vat_output_account_id',
        'vat_receivable_account_id',
        'cash_account_id',
        'bank_account_id',
        'undeposited_funds_account_id',
        'cogs_account_id',
        'inventory_account_id',

        // Summary
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

    // -------------------------------
    // Relationships
    // -------------------------------
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function receivableAccount() { return $this->belongsTo(ChartOfAccount::class, 'receivable_account_id'); }
    public function salesAccount() { return $this->belongsTo(ChartOfAccount::class, 'sales_account_id'); }
    public function exportSalesAccount() { return $this->belongsTo(ChartOfAccount::class, 'export_sales_account_id'); }
    public function salesReturnAccount() { return $this->belongsTo(ChartOfAccount::class, 'sales_return_account_id'); }
    public function salesDiscountAccount() { return $this->belongsTo(ChartOfAccount::class, 'sales_discount_account_id'); }
    public function customerAdvanceAccount() { return $this->belongsTo(ChartOfAccount::class, 'customer_advance_account_id'); }
    public function badDebtExpenseAccount() { return $this->belongsTo(ChartOfAccount::class, 'bad_debt_expense_account_id'); }
    public function allowanceDoubtfulAccount() { return $this->belongsTo(ChartOfAccount::class, 'allowance_for_doubtful_account_id'); }
    public function vatOutputAccount() { return $this->belongsTo(ChartOfAccount::class, 'vat_output_account_id'); }
    public function vatReceivableAccount() { return $this->belongsTo(ChartOfAccount::class, 'vat_receivable_account_id'); }
    public function cashAccount() { return $this->belongsTo(ChartOfAccount::class, 'cash_account_id'); }
    public function bankAccount() { return $this->belongsTo(ChartOfAccount::class, 'bank_account_id'); }
    public function undepositedFundsAccount() { return $this->belongsTo(ChartOfAccount::class, 'undeposited_funds_account_id'); }
    public function cogsAccount() { return $this->belongsTo(ChartOfAccount::class, 'cogs_account_id'); }
    public function inventoryAccount() { return $this->belongsTo(ChartOfAccount::class, 'inventory_account_id'); }

    // -------------------------------
    // Auto-fill Created By / Updated By
    // -------------------------------
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id() ?? 1;
            $model->updated_by = auth()->id() ?? 1;
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id() ?? 1;
        });
    }

    // -------------------------------
    // Accessors
    // -------------------------------
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance, 2);
    }

    public function getFormattedBalanceVatAttribute(): string
    {
        return number_format($this->balance_vat, 2);
    }
}
