<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CustomerOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity; 

    protected $primaryKey = 'order_id'; 

    protected $fillable = [
        'name',
        'wanted_delivery_date',
        'customer_id',
        'special_notes',
        'status',
        'added_by',  
        'random_code',
    ];

    protected static function booted()
    {
        static::creating(function ($customerOrder) {
            $customerOrder->random_code = '';
            for ($i = 0; $i < 16; $i++) {
                $customerOrder->random_code .= mt_rand(0, 9);
            }
        });
    }
    
    // Relationship with CustomerOrderDescription (OrderItems)
    public function orderItems()
    {
        return $this->hasMany(CustomerOrderDescription::class, 'customer_order_id', 'order_id');
    }

    // Relationship with Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    // Relationship to track the user who created the order (added_by)
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by'); // 'added_by' references the 'id' field in the users table
    }

    // Relationship with VariationItems (if required)
    public function variationItems()
    {
        return $this->hasManyThrough(
            VariationItem::class,
            CustomerOrderDescription::class,
            'customer_order_id', // Foreign key on CustomerOrderDescription
            'order_description_id', // Foreign key on VariationItem
            'order_id', // Local key on CustomerOrder
            'order_description_id' // Local key on CustomerOrderDescription
        );
    }

    // Configure activity log options
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('customer_order')
            ->setDescriptionForEvent(fn(string $eventName) => "Customer order with ID {$this->order_id} has been {$eventName}");
    }
}
