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
        'purchase_order_id',
        'register_arrival_id',
        'provider_type',
        'provider_id',
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
        static::creating(function ($invoice) {
            $invoice->created_by = auth()->id();
            $invoice->updated_by = auth()->id();

            $invoice->random_code = '';
            for ($i = 0; $i < 16; $i++) {
                $invoice->random_code .= mt_rand(0, 9);
            }

            if (is_null($invoice->due_payment_for_now)) {
                $invoice->due_payment_for_now = $invoice->due_payment ?? 0;
            }
        });

        static::updating(function ($invoice) {
            $invoice->updated_by = auth()->id();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'purchase_order_id',
                'provider_type',
                'provider_id',
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
