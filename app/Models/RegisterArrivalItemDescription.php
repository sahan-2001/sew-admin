<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RegisterArrivalItemDescription extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'register_arrival_item_id',
        'item_code',
        'quantity',
        'price',
        'total',
        'status',
        'is_po_item',
    ];

    protected static $logAttributes = [
        'register_arrival_item_id',
        'item_code',
        'quantity',
        'price',
        'total',
        'status',
        'is_po_item',
    ];

    protected static $logName = 'register_arrival_item_description';

    /**
     * Get the options for activity logging.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'register_arrival_item_id',
                'item_code',
                'quantity',
                'price',
                'total',
                'status',
                'is_po_item',
            ])
            ->useLogName('register_arrival_item_description')
            ->setDescriptionForEvent(function (string $eventName) {
                return "Register Arrival Item Description {$this->id} has been {$eventName}";
            });
    }

    public function registerArrivalItem()
    {
        return $this->belongsTo(RegisterArrivalItem::class);
    }
}