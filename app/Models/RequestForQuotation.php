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
        'order_subtotal',
        'random_code',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($rfq) {
            // Generate random 16-digit numeric code
            $rfq->random_code = collect(range(1, 16))
                ->map(fn () => mt_rand(0, 9))
                ->join('');

            $rfq->created_by = Auth::id() ?? 1;
            $rfq->updated_by = Auth::id() ?? 1;

            $rfq->order_subtotal = $rfq->order_subtotal ?? 0;
            $rfq->status         = $rfq->status ?? 'draft';
        });

        static::updating(function ($rfq) {
            $rfq->updated_by = Auth::id() ?? $rfq->updated_by;
            $rfq->order_subtotal = $rfq->order_subtotal ?? 0;
        });
    }

    /* -----------------------
     | RELATIONSHIPS
     ----------------------- */
    public function items(): HasMany
    {
        return $this->hasMany(RequestForQuotationItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* -----------------------
     | TOTALS CALCULATION
     ----------------------- */
    public function recalculateTotals(): void
    {
        $subTotal = $this->items()->sum('item_subtotal');

        $this->updateQuietly([
            'order_subtotal' => round($subTotal, 2),
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
                'valid_until',
                'special_note',
                'status',
                'order_subtotal',
            ])
            ->useLogName(self::$logName)
            ->setDescriptionForEvent(function (string $eventName) {
                $userId = Auth::id() ?? 'unknown';
                return "RFQ #{$this->id} has been {$eventName} by User {$userId}.";
            });
    }
}
