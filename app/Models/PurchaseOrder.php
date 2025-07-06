<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'provider_type',
        'provider_id',
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
        static::creating(function ($purchaseOrder) {
            $purchaseOrder->random_code = '';
            for ($i = 0; $i < 16; $i++) {
                $purchaseOrder->random_code .= mt_rand(0, 9);
            }

            $purchaseOrder->created_by = auth()->id();
            $purchaseOrder->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();

            if ($model->isDirty('status') && $model->status === 'closed') {
                $model->remaining_balance = 0;
            }
        });

        static::saved(function ($model) {
            $model->recalculateGrandTotal();
        });
    }

    public function recalculateGrandTotal()
    {
        $oldGrandTotal = $this->grand_total;
        $this->grand_total = $this->items()->sum('total_sale');
        
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

    protected static $logName = 'purchase_order';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'provider_type',
                'provider_id',
                'wanted_date',
                'special_note',
                'status', 
                'grand_total',
                'remaining_balance' 
            ])
            ->useLogName('purchase_order')
            ->setDescriptionForEvent(function (string $eventName) {
                $userEmail = $this->user ? $this->user->email : 'unknown';
                $description = "Purchase Order {$this->id} has been {$eventName} by User {$this->user_id}";

                if ($this->isDirty('status')) {
                    $description .= ". New status: {$this->status}";
                }

                return $description;
            });
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
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
}