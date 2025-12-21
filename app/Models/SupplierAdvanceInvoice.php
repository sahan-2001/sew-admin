<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SupplierAdvanceInvoice extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'purchase_order_id',
        'supplier_id', // Changed from provider_id
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

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
    
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id'); // Changed from provider_id
    }

    public function supplierControlAccount()
    {
        return $this->belongsTo(SupplierControlAccount::class, 'supplier_control_account_id');
    }

    public function supplierAdvanceAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'supplier_advance_account_id');
    }

    public function purchaseOrderAdvInvDeduction()
    {
        return $this->belongsTo(PurchaseOrderAdvInvDeduction::class);
    }

    public function payments()
    {
        return $this->hasMany(SuppAdvInvoicePayment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
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

            // Set initial status
            $invoice->status = 'pending';
            
            // Set initial paid amount
            if ($invoice->payment_type === 'fixed' && $invoice->fix_payment_amount) {
                $invoice->paid_amount = 0; // Initial paid amount is 0
                $invoice->remaining_amount = $invoice->fix_payment_amount;
            } elseif ($invoice->payment_type === 'percentage' && $invoice->percent_calculated_payment) {
                $invoice->paid_amount = 0; // Initial paid amount is 0
                $invoice->remaining_amount = $invoice->percent_calculated_payment;
            } else {
                $invoice->paid_amount = 0;
                $invoice->remaining_amount = 0;
            }
        });

        static::updating(function ($invoice) {
            $invoice->updated_by = auth()->id();
        });
    }

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