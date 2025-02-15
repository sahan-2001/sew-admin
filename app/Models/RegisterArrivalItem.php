<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RegisterArrivalItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'purchase_order_id',
        'received_date',
        'invoice_number',
        'invoice_image',
        'note',
        'location_status',
        'created_by',
    ];

    protected static $logAttributes = [
        'purchase_order_id',
        'received_date',
        'invoice_number',
        'invoice_image',
        'note',
        'location_status',
        'created_by',
    ];

    protected static $logName = 'register_arrival_item';

    /**
     * Get the options for activity logging.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'purchase_order_id',
                'received_date',
                'invoice_number',
                'invoice_image',
                'note',
                'location_status',
                'created_by',
            ])
            ->useLogName('register_arrival_item')
            ->setDescriptionForEvent(function (string $eventName) {
                $userEmail = $this->createdBy ? $this->createdBy->email : 'unknown';
                return "Register Arrival Item {$this->id} has been {$eventName} by User {$userEmail}";
            });
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function descriptions()
    {
        return $this->hasMany(RegisterArrivalItemDescription::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->id(); // Set the created_by field to the current user's ID
        });
    }
}