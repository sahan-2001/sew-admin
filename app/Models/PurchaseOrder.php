<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;

class PurchaseOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'supplier_id',
        'wanted_date',
        'special_note',
        'status', 
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
        });

        static::updating(function ($po) {
            $po->updated_by = Auth::id() ?? $po->updated_by;

            // If closing, set remaining balance to 0
            if ($po->isDirty('status') && $po->status === 'closed') {
                $po->remaining_balance = 0;
            }
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

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
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

    public function setGrandTotalAttribute($value)
    {
        $this->attributes['grand_total'] = $value;

        if (!isset($this->attributes['remaining_balance'])) {
            $this->attributes['remaining_balance'] = $value;
        } elseif (isset($this->attributes['remaining_balance']) && 
                 $this->attributes['remaining_balance'] == $this->getOriginal('grand_total')) {
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
                'wanted_date',
                'special_note',
                'status', 
                'grand_total',
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
