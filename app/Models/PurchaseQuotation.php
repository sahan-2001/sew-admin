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
        'supplier_id',
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
        'random_code',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($pq) {
            // Random code
            $pq->random_code = '';
            for ($i = 0; $i < 16; $i++) {
                $pq->random_code .= mt_rand(0, 9);
            }

            $pq->created_by = Auth::id() ?? 1;
            $pq->updated_by = Auth::id() ?? 1;

            $pq->order_subtotal = $pq->order_subtotal ?? 0;
            $pq->vat_amount     = $pq->vat_amount ?? 0;
            $pq->grand_total    = $pq->grand_total ?? 0;
            $pq->vat_base       = $pq->vat_base ?? 'item_vat';
            $pq->status         = $pq->status ?? 'draft';
        });

        static::updating(function ($pq) {
            $pq->updated_by = Auth::id() ?? $pq->updated_by;

            // Ensure totals are always filled
            $pq->order_subtotal = $pq->order_subtotal ?? 0;
            $pq->vat_amount     = $pq->vat_amount ?? 0;
            $pq->grand_total    = $pq->grand_total ?? 0;
            $pq->vat_base       = $pq->vat_base ?? 'item_vat';
        });

        // Recalculate totals after save
        static::saved(function ($pq) {
            $pq->recalculateTotals();
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

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supplierVatGroup()
    {
        return $this->belongsTo(VatGroup::class, 'supplier_vat_group_id');
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
