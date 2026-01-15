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
        'site_id',
        'supplier_id',
        'payment_term_id',
        'delivery_term_id',
        'delivery_method_id',
        'currency_code_id',
        'wanted_delivery_date',
        'valid_until',
        'special_note',
        'status',
        'random_code',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($rfq) {
            // Set site_id from session
            if (session()->has('site_id')) {
                $model->site_id = session('site_id');
            }
            
            // Generate random 16-digit numeric code
            $rfq->random_code = collect(range(1, 16))
                ->map(fn () => mt_rand(0, 9))
                ->join('');

            $rfq->created_by = Auth::id() ?? 1;
            $rfq->updated_by = Auth::id() ?? 1;

            $rfq->status         = $rfq->status ?? 'draft';
        });

        static::updating(function ($rfq) {
            $rfq->updated_by = Auth::id() ?? $rfq->updated_by;
        });
    }

    /* -----------------------
     | RELATIONSHIPS
     ----------------------- */
    public function items(): HasMany
    {
        return $this->hasMany(RequestForQuotationItem::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'request_for_quotation_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function rfq()
    {
        return $this->hasMany(PurchaseQuotation::class, 'request_for_quotation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
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
    
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code_id', 'id');
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
                'valid_until',
                'special_note',
                'status',
            ])
            ->useLogName(self::$logName)
            ->setDescriptionForEvent(function (string $eventName) {
                $userId = Auth::id() ?? 'unknown';
                return "RFQ #{$this->id} has been {$eventName} by User {$userId}.";
            });
    }
}
