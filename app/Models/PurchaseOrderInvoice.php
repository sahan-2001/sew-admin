<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseOrderInvoice extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'site_id',
        'purchase_order_id',
        'register_arrival_id',
        'supplier_id',
        'status',
        'grand_total',
        'adv_paid',
        'additional_cost',
        'discount',
        'total_calculation_method',
        'due_payment',
        'due_payment_for_now',
        'created_by',
        'updated_by',
        'random_code'
    ];

    public function invoiceItems()
    {
        return $this->hasMany(PurchaseOrderInvoiceItem::class);
    }

    public function supplierAdvanceInvoices()
    {
        return $this->hasMany(SupplierAdvanceInvoice::class, 'purchase_order_id', 'purchase_order_id')
            ->whereIn('status', ['paid', 'partially_paid']);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function registerArrival()
    {
        return $this->belongsTo(RegisterArrival::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderInvoiceItem::class);
    }

    public function additionalCosts()
    {
        return $this->hasMany(PoAddCost::class);
    }

    public function discounts()
    {
        return $this->hasMany(PurchaseOrderDiscount::class);
    }

    public function advanceInvoiceDeductions()
    {
        return $this->hasMany(PoAdvInvDeduct::class);
    }

    public function payments()
    {
        return $this->hasMany(PoInvoicePayment::class);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // Set site_id automatically if column exists
            if (isset($model->site_id) && session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            // Set created_by and updated_by
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            } else {
                $model->created_by = $model->created_by ?? 1;
                $model->updated_by = $model->updated_by ?? 1;
            }

            // Generate random_code if column exists
            if (isset($model->random_code) && empty($model->random_code)) {
                $model->random_code = '';
                for ($i = 0; $i < 16; $i++) {
                    $model->random_code .= mt_rand(0, 9);
                }
            }

            // Invoice-specific: initialize due_payment_for_now
            if (isset($model->due_payment_for_now) && is_null($model->due_payment_for_now)) {
                $model->due_payment_for_now = $model->due_payment ?? 0;
            }

            // Optional: other balances / totals / machine cost logic if columns exist
            if (isset($model->debit_total) && isset($model->credit_total)) {
                $model->balance = $model->debit_total - $model->credit_total;
            }
        });

        static::updating(function ($model) {
            // Update updated_by
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }

            // Optional recalculations
            if (isset($model->debit_total) && isset($model->credit_total)) {
                $model->balance = $model->debit_total - $model->credit_total;
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'purchase_order_id',
                'supplier_id',
                'status',
                'grand_total',
                'adv_paid',
                'additional_cost',
                'discount',
                'due_payment',
                'due_payment_for_now',
                'created_by',
                'updated_by',
                'random_code',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('purchase_order_invoice')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = auth()->user();
                $userInfo = $user ? " by {$user->name} (ID: {$user->id})" : "";
                return "PurchaseOrderInvoice #{$this->id} has been {$eventName}{$userInfo}";
            });
    }
}
