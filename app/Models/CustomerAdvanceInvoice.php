<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\Customer;


class CustomerAdvanceInvoice extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'order_type',
        'order_id',
        'customer_id',
        'grand_total',
        'amount',
        'created_by',
        'updated_by',
        'paid_date',
        'paid_via',
        'payment_reference',
        'cus_invoice_number',
        'invoice_image',
        'status',
        'created_by',
        'updated_by',
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
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });

        static::created(function ($model) {
            if ($model->customer) {
                $amount = is_numeric($model->amount) ? $model->amount : 0;
                $currentBalance = is_numeric($model->customer->remaining_balance) ? $model->customer->remaining_balance : 0;
                
                $newBalance = max($currentBalance - $amount, 0);
                
                $model->customer->update([
                    'remaining_balance' => $newBalance
                ]);
                
                activity()
                    ->performedOn($model->customer)
                    ->causedBy(auth()->user())
                    ->log("Advance payment of Rs. {$amount} recorded. Remaining balance updated from {$currentBalance} to {$newBalance}");
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
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
