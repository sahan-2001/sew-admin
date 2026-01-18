<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierControlAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'site_id',
        'supplier_id',

        // Core Payables
        // 'payable_account_id',
        'supplier_advance_account_id',

        // Purchase Related Accounts
        'purchase_account_id',
        'purchase_return_account_id',
        'purchase_discount_account_id',
        'freight_in_account_id',
        'grni_account_id',

        // VAT / Tax Accounts
        'vat_input_account_id',
        'vat_suspense_account_id',

        // Manufacturing Specific Accounts
        'direct_material_purchase_account_id',
        'indirect_material_purchase_account_id',
        'production_supplies_account_id',
        'subcontracting_expense_account_id',

        // Adjustments / Write-offs
        'bad_debt_recovery_account_id',
        'supplier_writeoff_account_id',
        'purchase_price_variance_account_id',

        // Totals
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

    // ----------------------------
    // Relationships
    // ----------------------------

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    // Core Payables
    public function supplierAdvanceAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'supplier_advance_account_id');
    }

    // Purchase Related Accounts
    public function purchaseAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'purchase_account_id');
    }

    public function purchaseReturnAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'purchase_return_account_id');
    }

    public function purchaseDiscountAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'purchase_discount_account_id');
    }

    public function freightInAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'freight_in_account_id');
    }

    public function grniAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'grni_account_id');
    }

    // VAT Control Accounts
    public function vatInputControlAccount()
    {
        return $this->belongsTo(VATControlAccount::class,'vat_input_account_id')->where('vat_account_type', 'purchase');
    }

    public function vatSuspenseControlAccount()
    {
        return $this->belongsTo(VATControlAccount::class,'vat_suspense_account_id')->where('vat_account_type', 'purchase');
    }

    // Manufacturing Specific Accounts
    public function directMaterialPurchaseAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'direct_material_purchase_account_id');
    }

    public function indirectMaterialPurchaseAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'indirect_material_purchase_account_id');
    }

    public function productionSuppliesAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'production_supplies_account_id');
    }

    public function subcontractingExpenseAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'subcontracting_expense_account_id');
    }

    // Adjustments / Write-offs
    public function badDebtRecoveryAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'bad_debt_recovery_account_id');
    }

    public function supplierWriteoffAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'supplier_writeoff_account_id');
    }

    public function purchasePriceVarianceAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'purchase_price_variance_account_id');
    }

    // ----------------------------
    // Auto Audit Fields
    // ----------------------------

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

        static::saving(function ($model) {
            // 🔒 ALWAYS calculate balances
            $model->balance =
                ($model->credit_total ?? 0) - ($model->debit_total ?? 0);

            $model->balance_vat =
                ($model->credit_total_vat ?? 0) - ($model->debit_total_vat ?? 0);
        });
    }
}
