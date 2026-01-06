<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestForQuotation extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'supplier_id',
        'wanted_delivery_date',
        'valid_until',
        'special_note',
        'status',
        'supplier_vat_group_id',
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
        static::creating(function ($rfq) {
            // Generate random 16-digit code
            $rfq->random_code = '';
            for ($i = 0; $i < 16; $i++) {
                $rfq->random_code .= mt_rand(0, 9);
            }

            $rfq->created_by = Auth::id() ?? 1;
            $rfq->updated_by = Auth::id() ?? 1;

            $rfq->order_subtotal = $rfq->order_subtotal ?? 0;
            $rfq->vat_amount     = $rfq->vat_amount ?? 0;
            $rfq->grand_total    = $rfq->grand_total ?? 0;
            $rfq->vat_base       = $rfq->vat_base ?? 'item_vat';
            $rfq->status         = $rfq->status ?? 'draft';
        });

        static::updating(function ($rfq) {
            $rfq->updated_by = Auth::id() ?? $rfq->updated_by;

            $rfq->order_subtotal = $rfq->order_subtotal ?? 0;
            $rfq->vat_amount     = $rfq->vat_amount ?? 0;
            $rfq->grand_total    = $rfq->grand_total ?? 0;
            $rfq->vat_base       = $rfq->vat_base ?? 'item_vat';
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
        return $this->hasMany(RequestForQuotationItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supplierVatGroup()
    {
        return $this->belongsTo(SupplierVatGroup::class, 'supplier_vat_group_id');
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

        if ($items->isEmpty()) {
            $this->updateQuietly([
                'order_subtotal' => 0,
                'vat_amount'     => 0,
                'grand_total'    => 0,
            ]);
            return;
        }

        $subTotal = $items->sum('item_subtotal');

        if ($this->vat_base === 'supplier_vat') {
            $vatAmount  = round(($subTotal * ($this->supplier_vat_rate ?? 0)) / 100, 2);
            $grandTotal = round($subTotal + $vatAmount, 2);
        } else {
            $vatAmount  = $items->sum('item_vat_amount');
            $grandTotal = $items->sum('item_grand_total');
        }

        $this->updateQuietly([
            'order_subtotal' => round($subTotal, 2),
            'vat_amount'     => round($vatAmount, 2),
            'grand_total'    => round($grandTotal, 2),
        ]);
    }

    /* -----------------------
     | ACTIVITY LOG
     ----------------------- */
    protected static $logName = 'request_for_quotation';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'supplier_id',
                'wanted_delivery_date',
                'supplier_vat_group_id',
                'valid_until',
                'special_note',
                'status',
                'grand_total',
                'order_subtotal',
                'vat_amount',
                'vat_base',
            ])
            ->useLogName(self::$logName)
            ->setDescriptionForEvent(function (string $eventName) {
                $userId = Auth::id() ?? 'unknown';
                return "RFQ #{$this->id} has been {$eventName} by User {$userId}.";
            });
    }
}
