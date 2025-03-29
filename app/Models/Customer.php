<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'name',
        'shop_name',
        'address',
        'email',
        'phone_1',
        'phone_2',
        'remaining_balance',
        'requested_by',
        'approved_by',
    ];

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    protected static $logAttributes = [
        'name',
        'shop_name',
        'address',
        'email',
        'phone_1',
        'phone_2',
        'remaining_balance',
        'requested_by',
        'approved_by',
    ];

    protected static $logName = 'customer';

    /**
     * Get the options for activity logging.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'shop_name',
                'address',
                'email',
                'phone_1',
                'phone_2',
                'remaining_balance',
                'requested_by',
                'approved_by',
            ])
            ->useLogName('customer')
            ->setDescriptionForEvent(fn(string $eventName) => "Customer {$this->customer_id} has been {$eventName} by User {$this->requested_by} ({$this->requestedBy->email})");
    }
}