<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SupplierAdvanceInvoice extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'site_id',
        'purchase_order_id',
        'supplier_id',
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
        'supplier_control_account_id',
        'supplier_advance_account_id',
    ];

    protected $casts = [
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'fix_payment_amount' => 'decimal:2',
        'percent_calculated_payment' => 'decimal:2',
        'payment_percentage' => 'decimal:2',
        'paid_date' => 'date',
    ];

    // -----------------------------
    // Relationships
    // -----------------------------
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function supplierControlAccount()
    {
        return $this->belongsTo(SupplierControlAccount::class, 'supplier_control_account_id');
    }

    public function supplierAdvanceAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'supplier_advance_account_id');
    }

    public function payments()
    {
        return $this->hasMany(SuppAdvInvoicePayment::class, 'supplier_advance_invoice_id');
    }

    // -----------------------------
    // Booted Hooks
    // -----------------------------
    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (session()->has('site_id')) {
                $invoice->site_id = session('site_id');
            }

            $invoice->created_by = auth()->id();
            $invoice->updated_by = auth()->id();

            // Generate random 16-digit code
            $invoice->random_code = '';
            for ($i = 0; $i < 16; $i++) {
                $invoice->random_code .= mt_rand(0, 9);
            }

            $invoice->status = 'pending';
            $invoice->paid_amount = 0;

            if ($invoice->payment_type === 'fixed' && $invoice->fix_payment_amount) {
                $invoice->remaining_amount = $invoice->fix_payment_amount;
            } elseif ($invoice->payment_type === 'percentage' && $invoice->percent_calculated_payment) {
                $invoice->remaining_amount = $invoice->percent_calculated_payment;
            } else {
                $invoice->remaining_amount = 0;
            }
        });

        static::updating(function ($invoice) {
            $invoice->updated_by = auth()->id();
        });

        // -----------------------------
        // Soft delete adjustments
        // -----------------------------
        static::deleting(function ($invoice) {
            // ❌ Prevent deleting if payments exist
            if ($invoice->payments()->exists()) {
                throw new \Exception('Cannot delete Supplier Advance Invoice with payments.');
            }

            // ✅ Get the amount to reverse
            $amount = $invoice->remaining_amount;

            // 🔄 Reverse Supplier Control Account (liability → credit)
            $supplierControl = $invoice->supplierControlAccount;
            if ($supplierControl) {
                $supplierControl->decrement('credit_total', $amount);
                $supplierControl->decrement('credit_total_vat', $amount);
                $supplierControl->decrement('balance', $amount);
                $supplierControl->decrement('balance_vat', $amount);
            }

            // 🔄 Reverse Supplier Advance Account (asset → debit)
            $advanceAccount = $invoice->supplierAdvanceAccount;
            if ($advanceAccount) {
                $advanceAccount->decrement('debit_total', $amount);
                $advanceAccount->decrement('debit_total_vat', $amount);
                $advanceAccount->decrement('balance', $amount);
                $advanceAccount->decrement('balance_vat', $amount);
            }

            // 🧾 Delete related ledger entries
            \App\Models\SupplierLedgerEntry::where('reference_table', 'supplier_advance_invoices')
                ->where('reference_record_id', $invoice->id)
                ->delete();

            \App\Models\GeneralLedgerEntry::where('reference_table', 'supplier_advance_invoices')
                ->where('reference_record_id', $invoice->id)
                ->delete();
        });

    }

    // -----------------------------
    // Activity Log
    // -----------------------------
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('supplier_advance_invoice')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = auth()->user();
                $userInfo = $user ? " by {$user->name} (ID: {$user->id})" : "";
                return "SupplierAdvanceInvoice #{$this->id} has been {$eventName}{$userInfo}";
            });
    }
}
