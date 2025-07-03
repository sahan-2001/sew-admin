<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CustomerAdvanceInvoice extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'order_type',
        'order_id',
        'grand_total',
        'payment_type',
        'fix_payment_amount',
        'payment_percentage',
        'percent_calculated_payment',
        'created_by',
        'updated_by',
        'received_amount',
        'paid_date',
        'paid_via',
        'cus_invoice_number',
        'invoice_image',
    ];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $logName = 'customer_advance_invoice';

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function customerOrder()
    {
        return $this->belongsTo(\App\Models\CustomerOrder::class, 'order_id');
    }

    public function sampleOrder()
    {
        return $this->belongsTo(\App\Models\SampleOrder::class, 'order_id');
    }

    protected static function booted()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();

            if ($model->fix_payment_amount) {
                $model->received_amount = $model->fix_payment_amount;
            } elseif ($model->percent_calculated_payment) {
                $model->received_amount = $model->percent_calculated_payment;
            } else {
                $model->received_amount = 0; 
            }
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('customer_advance_invoice')
            ->setDescriptionForEvent(function (string $eventName) {
                $changes = $this->getDirty();
                $description = "Customer Advance Invoice #{$this->id} was {$eventName}";

                if ($eventName === 'updated' && !empty($changes)) {
                    $description .= ". Changes: " . json_encode($changes);
                }

                $user = auth()->user();
                if ($user) {
                    $description .= " by {$user->name} (ID: {$user->id})";
                }

                return $description;
            });
    }

}
