<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class CustomerOrderDescription extends Model
{
    use LogsActivity;

    // The table associated with the model.
    protected $table = 'customer_order_descriptions';

    // Optionally, define other fields that should be mass assignable
    protected $fillable = ['customer_order_id', 'item_name', 'variation_name', 'quantity', 'price', 'note', 'total', 'is_variation'];

    // Define the relationship to the CustomerOrder model
    public function customerOrder()
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id', 'order_id');
    }

    // Define the relationship to the VariationItem model
    public function variationItems()
    {
        return $this->hasMany(VariationItem::class, 'customer_order_description_id');
    }

    // Configure activity log options
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('customer_order_description')
            ->setDescriptionForEvent(fn(string $eventName) => "Order description for customer order with ID {$this->customer_order_id} has been {$eventName}");
    }

    // Override the tapActivity method to include additional properties
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'attributes' => $this->getAttributes(),
        ]);
    }
}
