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
        'supplier_id',
        'wanted_delivery_date',
        'promised_delivery_date',
        'special_note',
        'status', 
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
        // Automatically set creator/updater and random code
        static::creating(function ($po) {
            $po->random_code = '';
            for ($i = 0; $i < 16; $i++) {
                $po->random_code .= mt_rand(0, 9);
            }

            $po->created_by = Auth::id() ?? 1;
            $po->updated_by = Auth::id() ?? 1;

            // Ensure totals are set before creating
            $po->order_subtotal = $po->order_subtotal ?? 0;
            $po->vat_amount     = $po->vat_amount ?? 0;
            $po->grand_total    = $po->grand_total ?? 0;
            $po->remaining_balance = $po->grand_total;
            $po->vat_base       = $po->vat_base ?? 'item_vat';
        });

        static::updating(function ($po) {
            $po->updated_by = Auth::id() ?? $po->updated_by;

            // If closing, set remaining balance to 0
            if ($po->isDirty('status') && $po->status === 'closed') {
                $po->remaining_balance = 0;
            }

            // Ensure totals are always filled
            $po->order_subtotal = $po->order_subtotal ?? 0;
            $po->vat_amount     = $po->vat_amount ?? 0;
            $po->grand_total    = $po->grand_total ?? 0;
            $po->remaining_balance = $po->remaining_balance ?? $po->grand_total;
            $po->vat_base       = $po->vat_base ?? 'item_vat';
        });

        // Recalculate grand total after save
        static::saved(function ($po) {
            $po->recalculateGrandTotal();
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

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
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
    public function recalculateGrandTotal()
    {
        $oldGrandTotal = $this->grand_total;
        $this->grand_total = $this->items()->sum('total_sale');

        // Only reset remaining balance if it matches old total
        if (is_null($this->remaining_balance) || $this->remaining_balance == $oldGrandTotal) {
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
                'wanted_delivery_date',
                'promised_delivery_date',
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
