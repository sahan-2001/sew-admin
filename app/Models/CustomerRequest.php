<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CustomerRequest extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'site_id',
        'name',
        'shop_name',
        'address',
        'email',
        'phone_1',
        'phone_2',
        'remaining_balance',
        'requested_by',
        'approved_by',
        'status',
    ];

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
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
        'status',
        'created_by',
        'updated_by',
    ];

    protected static $logName = 'customer_request';

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
                'status',
            ])
            ->useLogName('customer_request')
            ->setDescriptionForEvent(fn(string $eventName) => "Customer Request {$this->id} has been {$eventName} by User {$this->requested_by} ({$this->requestedBy->email})");
    }
}