<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'site_id',
        'supplier_id',
        'purchase_quotation_id',
        'request_for_quotation_id',
        'payment_term_id',
        'delivery_term_id',
        'delivery_method_id',
        'currency_code_id',
        'wanted_delivery_date',
        'promised_delivery_date',
        'special_note',
        'status',

        'supplier_vat_group_id',
        'supplier_vat_rate',

        'order_subtotal',
        'vat_amount',
        'vat_base',
        'grand_total',

        'order_discount_type',
        'order_discount_value',
        'order_discount_amount',
        'final_grand_total',
        'remaining_balance',

        'random_code',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {

            if (session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            if (auth()->check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            } else {
                $model->created_by ??= 1;
                $model->updated_by ??= 1;
            }

            // Random code
            if (empty($model->random_code)) {
                $model->random_code = strtoupper(str()->random(16));
            }

            // Defaults
            $model->order_subtotal ??= 0;
            $model->vat_amount ??= 0;
            $model->grand_total ??= 0;
            $model->vat_base ??= 'item_vat';

            // Final grand total after order discount
            $model->final_grand_total ??= $model->grand_total - ($model->order_discount_amount ?? 0);

            // Remaining balance defaults to final grand total
            $model->remaining_balance ??= $model->final_grand_total;
        });

        static::updating(function ($model) {

            if (auth()->check()) {
                $model->updated_by = Auth::id();
            }

            if ($model->isDirty('grand_total') || $model->isDirty('order_discount_amount')) {
                $model->final_grand_total = $model->grand_total - ($model->order_discount_amount ?? 0);

                if ($model->remaining_balance == $model->getOriginal('final_grand_total')) {
                    $model->remaining_balance = $model->final_grand_total;
                }
            }

            if ($model->isDirty('status') && $model->status === 'closed') {
                $model->remaining_balance = 0;
            }
        });
    }

    /* ---------------------------------
     | Relationships
     |---------------------------------*/
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id'); // foreignKey, ownerKey
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function supplierAdvanceInvoices()
    {
        return $this->hasMany(SupplierAdvanceInvoice::class);
    }

    public function invoice()
    {
        return $this->hasOne(PurchaseOrderInvoice::class);
    }

    public function supplierVatGroup()
    {
        return $this->belongsTo(VatGroup::class, 'supplier_vat_group_id');
    }

    /* ---------------------------------
     | Mutators
     |---------------------------------*/
    public function setOrderSubtotalAttribute($value)
    {
        $this->attributes['order_subtotal'] = $value ?? 0;
    }

    public function setVatAmountAttribute($value)
    {
        $this->attributes['vat_amount'] = $value ?? 0;
    }

    public function setGrandTotalAttribute($value)
    {
        $this->attributes['grand_total'] = $value ?? 0;
    }

    public function setVatBaseAttribute($value)
    {
        $this->attributes['vat_base'] = $value ?? 'item_vat';
    }

    public function setRemainingBalanceAttribute($value)
    {
        $this->attributes['remaining_balance'] =
            $value ?? ($this->final_payable_amount ?? 0);
    }

    public function deliveryTerm()
    {
        return $this->belongsTo(DeliveryTerm::class, 'delivery_term_id');
    }

    public function deliveryMethod()
    {
        return $this->belongsTo(DeliveryMethod::class, 'delivery_method_id');
    }

    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class, 'payment_term_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code_id');
    }


    /* ---------------------------------
     | Totals Recalculation
     |---------------------------------*/
    public function recalculateTotals(): void
    {
        $items = $this->items()->get();
        $subTotal = round($items->sum('item_subtotal'), 2);

        if ($this->vat_base === 'supplier_vat') {
            $vatAmount  = round(($subTotal * $this->supplier_vat_rate) / 100, 2);
            $grandTotal = round($subTotal + $vatAmount, 2);

            $this->vat_amount  = $vatAmount;
            $this->grand_total = $grandTotal;
        } else {
            $this->vat_amount  = 0;
            $this->grand_total = round($items->sum('item_grand_total'), 2);
        }

        $this->order_subtotal = $subTotal;

        if ($this->remaining_balance == $this->getOriginal('final_payable_amount')) {
            $this->remaining_balance = $this->final_payable_amount;
        }

        $this->saveQuietly();
    }

    /* ---------------------------------
     | Activity Log
     |---------------------------------*/
    protected static $logName = 'purchase_order';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'supplier_id',
                'wanted_delivery_date',
                'promised_delivery_date',
                'supplier_vat_group_id',
                'supplier_vat_rate',
                'status',
                'order_subtotal',
                'vat_amount',
                'grand_total',
                'final_discount_amount',
                'final_payable_amount',
                'remaining_balance',
            ])
            ->useLogName('purchase_order');
    }
}
