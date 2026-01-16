<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseQuotation extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'site_id',
        'supplier_id',
        'request_for_quotation_id',
        'payment_term_id',
        'delivery_term_id',
        'delivery_method_id',
        'currency_code_id',
        'wanted_delivery_date',
        'quotation_date',
        'valid_until',
        'special_note',
        'status',
        'supplier_vat_rate',
        'order_subtotal',
        'vat_amount',
        'vat_base',
        'grand_total',
        'supplier_quotation_number',
        'received_date',
        'estimated_delivery_date',
        'supplier_note',
        'image_of_quotation',
        'random_code',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quotation_date' => 'datetime',
        'wanted_delivery_date' => 'datetime',
        'valid_until' => 'datetime',
        'received_date' => 'datetime',
        'estimated_delivery_date' => 'datetime',
    ];


    protected static function booted()
    {
        static::creating(function ($model) {
            // Only set site_id if session has it
            if (session()->has('site_id')) {
                $model->site_id = session('site_id');
            } else {
                // Throw an exception instead of inserting invalid data
                throw new \Exception('No active site selected. Cannot create this record.');
            }

            $model->created_by = Auth::id();
            $model->updated_by = Auth::id();

            //  Random code
            if (isset($model->random_code) && empty($model->random_code)) {
                $model->random_code = '';
                for ($i = 0; $i < 16; $i++) {
                    $model->random_code .= mt_rand(0, 9);
                }
            }

            //  Barcode ID
            if (isset($model->barcode_id) && empty($model->barcode_id)) {
                $model->barcode_id = uniqid('CUT');
            }

            //  Totals (PO/PQ/Invoice)
            if (isset($model->order_subtotal)) {
                $model->order_subtotal    = $model->order_subtotal ?? 0;
                $model->vat_amount        = $model->vat_amount ?? 0;
                $model->grand_total       = $model->grand_total ?? 0;
                $model->remaining_balance = $model->grand_total ?? 0;
                $model->vat_base          = $model->vat_base ?? 'item_vat';
                $model->status            = $model->status ?? 'draft';
            }

            //  Machine cost calculations
            if (isset($model->purchased_cost)) {
                $model->total_initial_cost = $model->purchased_cost + ($model->additional_cost ?? 0);
                $model->net_present_value  = $model->total_initial_cost - ($model->cumulative_depreciation ?? 0);
            }

            //  Line items remaining quantity
            if (isset($model->quantity) && isset($model->remaining_quantity)) {
                $model->remaining_quantity = $model->quantity;
            }

            //  Debit/credit balances
            if (isset($model->debit_total) && isset($model->credit_total)) {
                $model->balance = $model->debit_total - $model->credit_total;
            }
            if (isset($model->debit_total_vat) && isset($model->credit_total_vat)) {
                $model->balance_vat = $model->debit_total_vat - $model->credit_total_vat;
            }
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id() ?? $model->updated_by ?? 1;

            // Recalculate totals or balances on update
            if (isset($model->order_subtotal)) {
                $model->order_subtotal    = $model->order_subtotal ?? 0;
                $model->vat_amount        = $model->vat_amount ?? 0;
                $model->grand_total       = $model->grand_total ?? 0;
                $model->remaining_balance = $model->remaining_balance ?? $model->grand_total;
                $model->vat_base          = $model->vat_base ?? 'item_vat';
            }

            if (isset($model->purchased_cost)) {
                $model->total_initial_cost = $model->purchased_cost + ($model->additional_cost ?? 0);
                $model->net_present_value  = $model->total_initial_cost - ($model->cumulative_depreciation ?? 0);
            }

            if (isset($model->debit_total) && isset($model->credit_total)) {
                $model->balance = $model->debit_total - $model->credit_total;
            }
            if (isset($model->debit_total_vat) && isset($model->credit_total_vat)) {
                $model->balance_vat = $model->debit_total_vat - $model->credit_total_vat;
            }

            // Update remaining quantity for line items if needed
            if (isset($model->quantity) && isset($model->remaining_quantity) && $model->remaining_quantity === null) {
                $model->remaining_quantity = $model->quantity;
            }
        });

        static::saved(function ($model) {
            if (method_exists($model, 'recalculateTotals')) {
                $model->recalculateTotals();
            }
        });

        static::deleted(function ($model) {
            if (method_exists($model, 'recalculateTotals')) {
                $model->recalculateTotals();
            }
        });
    }

    /* -----------------------
     | RELATIONSHIPS
     ----------------------- */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(\App\Models\PurchaseQuotationItem::class);
    }

    public function rfq()
    {
        return $this->belongsTo(RequestForQuotation::class, 'request_for_quotation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supplierVatGroup()
    {
        return $this->belongsTo(VatGroup::class, 'supplier_vat_group_id');
    }

    public function paymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class, 'payment_term_id');
    }
    
    public function deliveryTerm()
    {
        return $this->belongsTo(DeliveryTerm::class, 'delivery_term_id');
    }

    public function deliveryMethod()
    {
        return $this->belongsTo(DeliveryMethod::class, 'delivery_method_id');
    }

    public function currencyCode()
    {
        return $this->belongsTo(Currency::class, 'currency_code_id', 'code');
    }
    
    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    /* -----------------------
     | ATTRIBUTE SETTERS
     ----------------------- */
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

    /* -----------------------
     | TOTALS CALCULATION
     ----------------------- */
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
            // Item-based VAT
            $this->vat_amount  = 0;
            $this->grand_total = $items->sum('item_grand_total');
        }

        $this->order_subtotal = $subTotal;

        $this->saveQuietly();
    }
    

    /* -----------------------
     | ACTIVITY LOG
     ----------------------- */
    protected static $logName = 'purchase_quotation';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'supplier_id',
                'wanted_delivery_date',
                'quotation_date',
                'valid_until',
                'special_note',
                'status',
                'grand_total',
                'order_subtotal',
                'vat_amount',
                'vat_base',
            ])
            ->useLogName('purchase_quotation')
            ->setDescriptionForEvent(function (string $eventName) {
                $userId = Auth::id() ?? 'unknown';
                return "Purchase Quotation {$this->id} has been {$eventName} by User {$userId}.";
            });
    }
}
