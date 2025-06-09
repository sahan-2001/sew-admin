<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Action;

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
        'added_by', 
        'accepted_by',
        'confirmation_message',
        'rejected_by',
        'rejection_message',
        'random_code', 
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($sampleOrder) {
            $sampleOrder->random_code = '';
            for ($i = 0; $i < 16; $i++) {
                $sampleOrder->random_code .= mt_rand(0, 9);
            }
        });

        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
    
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
        return $this->belongsTo(User::class, 'added_by');
    }

    public function actions(): MorphMany
    {
        return $this->morphMany(Action::class, 'model');
    }

    // Configure activity log options
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('sample_order')
            ->setDescriptionForEvent(function (string $eventName) {
                $description = "Sample order with ID {$this->order_id} has been {$eventName}";

                // Add updated status to the description if status is changed
                if ($this->isDirty('status')) {
                    $description .= ". New status: {$this->status}";
                }

                return $description;
            });
    }

}
