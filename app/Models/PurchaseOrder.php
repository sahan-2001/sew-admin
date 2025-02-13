<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'provider_type',
        'provider_id',
        'provider_name',
        'provider_email',
        'provider_phone',
        'wanted_date',
        'special_note',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function provider()
    {
        if ($this->provider_type === 'supplier') {
            return $this->belongsTo(Supplier::class, 'provider_id', 'supplier_id');
        } elseif ($this->provider_type === 'customer') {
            return $this->belongsTo(Customer::class, 'provider_id', 'customer_id');
        }
        return null;
    }

    protected static $logAttributes = [
        'provider_type',
        'provider_id',
        'provider_name',
        'provider_email',
        'provider_phone',
        'wanted_date',
        'special_note',
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
            ])
            ->useLogName('purchase_order')
            ->setDescriptionForEvent(fn(string $eventName) => "Purchase Order {$this->id} has been {$eventName} by User {$this->user_id} ({$this->user->email})");
    }
}