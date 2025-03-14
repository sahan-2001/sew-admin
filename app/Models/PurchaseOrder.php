<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'provider_type',
        'provider_id',
        'provider_name',
        'provider_email',
        'provider_phone',
        'wanted_date',
        'special_note',
        'user_id',
        'status', // Add status to fillable
    ];

    protected static $logName = 'purchase_order';

    /**
     * Get the options for activity logging.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'provider_type',
                'provider_id',
                'provider_name',
                'provider_email',
                'provider_phone',
                'wanted_date',
                'special_note',
                'status', // Log changes to status
            ])
            ->useLogName('purchase_order')
            ->setDescriptionForEvent(function (string $eventName) {
                $userEmail = $this->user ? $this->user->email : 'unknown';
                $description = "Purchase Order {$this->id} has been {$eventName} by User {$this->user_id}";

                // Add updated status to the description if status is changed
                if ($this->isDirty('status')) {
                    $description .= ". New status: {$this->status}";
                }

                return $description;
            });
    }

    /**
     * Get the user that owns the purchase order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the purchase order.
     */
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}