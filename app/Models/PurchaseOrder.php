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
        'remaining_balance', 
        'random_code', 
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            // Set site_id if column exists
            if (isset($model->site_id) && session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            //  Set created_by / updated_by
            if (auth()->check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            } else {
                $model->created_by = $model->created_by ?? 1;
                $model->updated_by = $model->updated_by ?? 1;
            }

            //  Generate random_code if column exists
            if (isset($model->random_code) && empty($model->random_code)) {
                $model->random_code = '';
                for ($i = 0; $i < 16; $i++) {
                    $model->random_code .= mt_rand(0, 9);
                }
            }

            //  Generate barcode_id if column exists
            if (isset($model->barcode_id) && empty($model->barcode_id)) {
                $model->barcode_id = uniqid('CUT');
            }

            // Handle debit/credit balances if columns exist
            if (isset($model->debit_total) && isset($model->credit_total)) {
                $model->balance = $model->debit_total - $model->credit_total;
            }

            if (isset($model->debit_total_vat) && isset($model->credit_total_vat)) {
                $model->balance_vat = $model->debit_total_vat - $model->credit_total_vat;
            }

            //  Handle order totals if columns exist
            if (isset($model->order_subtotal)) {
                $model->order_subtotal     = $model->order_subtotal ?? 0;
                $model->vat_amount         = $model->vat_amount ?? 0;
                $model->grand_total        = $model->grand_total ?? 0;
                $model->remaining_balance  = $model->grand_total;
                $model->vat_base           = $model->vat_base ?? 'item_vat';
            }

            //  Handle machine cost calculations if columns exist
            if (isset($model->purchased_cost)) {
                $model->total_initial_cost   = $model->purchased_cost + ($model->additional_cost ?? 0);
                $model->net_present_value    = $model->total_initial_cost - ($model->cumulative_depreciation ?? 0);
            }
        });

        static::updating(function ($model) {
            // Update audit
            if (auth()->check()) {
                $model->updated_by = Auth::id();
            }

            // Recalculate balances if applicable
            if (isset($model->debit_total) && isset($model->credit_total)) {
                $model->balance = $model->debit_total - $model->credit_total;
            }

            if (isset($model->debit_total_vat) && isset($model->credit_total_vat)) {
                $model->balance_vat = $model->debit_total_vat - $model->credit_total_vat;
            }

            // Recalculate order totals if applicable
            if (isset($model->order_subtotal)) {
                $model->order_subtotal     = $model->order_subtotal ?? 0;
                $model->vat_amount         = $model->vat_amount ?? 0;
                $model->grand_total        = $model->grand_total ?? 0;
                $model->remaining_balance  = $model->remaining_balance ?? $model->grand_total;

                // Optional: if status changed to closed
                if ($model->isDirty('status') && $model->status === 'closed') {
                    $model->remaining_balance = 0;
                }

                $model->vat_base = $model->vat_base ?? 'item_vat';
            }

            // Recalculate machine costs if applicable
            if (isset($model->purchased_cost)) {
                $model->total_initial_cost = $model->purchased_cost + ($model->additional_cost ?? 0);
                $model->net_present_value  = $model->total_initial_cost - ($model->cumulative_depreciation ?? 0);
            }
        });

        // Optional: after save, recalc totals if the model has recalculateTotals method
        static::saved(function ($model) {
            if (method_exists($model, 'recalculateTotals')) {
                $model->recalculateTotals();
            }
        });
    }

    /**
     * Relationships
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(\App\Models\PurchaseOrderItem::class);
    }

    public function supplierAdvanceInvoices()
    {
        return $this->hasMany(SupplierAdvanceInvoice::class);
    }

    public function invoice()
    {
        return $this->hasOne(\App\Models\PurchaseOrderInvoice::class, 'purchase_order_id');
    }

    public function supplierVatGroup()
    {
        return $this->belongsTo(VatGroup::class, 'supplier_vat_group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function deliveryTerm()
    {
        return $this->belongsTo(DeliveryTerm::class);
    }

    public function purchaseQuotation()
    {
        return $this->belongsTo(PurchaseQuotation::class);
    }

    public function requestForQuotation()
    {
        return $this->belongsTo(RequestForQuotation::class);
    }

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

        if (!isset($this->attributes['remaining_balance'])) {
            $this->attributes['remaining_balance'] = $value;
        } elseif ($this->attributes['remaining_balance'] == $this->getOriginal('grand_total')) {
            $this->attributes['remaining_balance'] = $value;
        }
    }

    public function setVatBaseAttribute($value)
    {
        $this->attributes['vat_base'] = $value ?? 'item_vat';
    }

    /**
     * Totals
     */
    public function recalculateTotals(): void
    {
        $items = $this->items()->get();
        $subTotal = $items->sum('item_subtotal');

        if ($this->vat_base === 'supplier_vat') {
            $vatAmount  = round(($subTotal * $this->supplier_vat_rate) / 100, 2);
            $grandTotal = round($subTotal + $vatAmount, 2);
            $this->vat_amount  = $vatAmount;
            $this->grand_total = $grandTotal;
        } else {
            // Item-based VAT → calculate from items, but do NOT save in purchase_order.vat_amount
            $this->vat_amount  = 0; 
            $this->grand_total = $items->sum('item_grand_total');
        }

        $this->order_subtotal = $subTotal;

        // Keep remaining balance aligned
        if ($this->remaining_balance == $this->getOriginal('grand_total')) {
            $this->remaining_balance = $this->grand_total;
        }

        $this->saveQuietly();
    }

    public function setRemainingBalanceToGrandTotal()
    {
        $this->remaining_balance = $this->grand_total;
        $this->saveQuietly();
    }

    public function setRemainingBalanceAttribute($value)
    {
        if (is_null($value) && !is_null($this->grand_total)) {
            $this->attributes['remaining_balance'] = $this->grand_total;
        } else {
            $this->attributes['remaining_balance'] = $value;
        }
    }

    /**
     * Activity Log
     */
    protected static $logName = 'purchase_order';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'supplier_id',
                'purchase_quotation_id',
                'wanted_delivery_date',
                'promised_delivery_date',
                'supplier_vat_group_id',
                'supplier_vat_rate',
                'special_note',
                'status', 
                'grand_total',
                'order_subtotal',
                'vat_amount',
                'vat_base',
                'remaining_balance',
            ])
            ->useLogName('purchase_order')
            ->setDescriptionForEvent(function (string $eventName) {
                $userId = Auth::id() ?? 'unknown';
                $description = "Purchase Order {$this->id} has been {$eventName} by User {$userId}";
                
                if ($this->isDirty('status')) {
                    $description .= ". New status: {$this->status}";
                }

                return $description;
            });
    }
}
