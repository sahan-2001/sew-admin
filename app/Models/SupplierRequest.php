<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SupplierRequest extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'shop_name',
        'address',
        'email',
        'phone_1',
        'phone_2',
        'outstanding_balance',
        'requested_by',
        'approved_by',
        'note',
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

    protected static $logAttributes = [
        'name',
        'shop_name',
        'address',
        'email',
        'phone_1',
        'phone_2',
        'outstanding_balance',
        'requested_by',
        'approved_by',
        'note',
        'status',
    ];

    protected static $logName = 'supplier_request';

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
                'outstanding_balance',
                'requested_by',
                'approved_by',
                'note',
                'status',
            ])
            ->useLogName('supplier_request')
            ->setDescriptionForEvent(fn(string $eventName) => "Supplier Request {$this->id} has been {$eventName} by User {$this->requested_by} ({$this->requestedBy->email})");
    }
}