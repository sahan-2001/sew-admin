<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SampleOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity; // Enable soft deletes and activity logging

    protected $primaryKey = 'order_id'; // Set the primary key to 'order_id'

    public $incrementing = true; // Indicates that the primary key is auto-incrementing

    protected $keyType = 'int'; // Primary key is of integer type

    protected $fillable = [
        'name',
        'customer_id',
        'wanted_delivery_date',
        'special_notes',
        'status',
        'added_by', // Track the user who created the order
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    
    public function items()
    {
        return $this->hasMany(SampleOrderItem::class, 'sample_order_id');
    }

    public function variations()
    {
        return $this->hasManyThrough(SampleOrderVariation::class, SampleOrderItem::class, 'sample_order_id', 'sample_order_item_id');
    }

    // Relationship to track the user who created the order (added_by)
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by'); // 'added_by' references the 'id' field in the users table
    }

    // Configure activity log options
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('sample_order')
            ->setDescriptionForEvent(fn(string $eventName) => "Sample order with ID {$this->order_id} has been {$eventName}");
    }
}
