<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PoInvoicePayment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'site_id',
        'purchase_order_invoice_id',
        'payment_amount',
        'remaining_amount_before',
        'remaining_amount_after',
        'payment_method',
        'payment_reference',
        'notes',
        'paid_by',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'payment_amount' => 'decimal:2',
        'remaining_amount_before' => 'decimal:2',
        'remaining_amount_after' => 'decimal:2',
    ];

    public function purchaseOrderInvoice()
    {
        return $this->belongsTo(PurchaseOrderInvoice::class);
    }

    public function paidByUser()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // Set site_id from session
            if (session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });
        

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'purchase_order_invoice_id',
                'payment_amount',
                'remaining_amount_before',
                'remaining_amount_after',
                'payment_method',
                'payment_reference',
                'notes',
                'paid_by',
                'paid_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('po_invoice_payment')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = auth()->user();
                $userInfo = $user ? " by {$user->name} (ID: {$user->id})" : "";
                return "PoInvoicePayment #{$this->id} has been {$eventName}{$userInfo}";
            });
    }
}
