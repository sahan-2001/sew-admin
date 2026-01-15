<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class CustomerOrderDescription extends Model
{
    use LogsActivity;

    protected $table = 'customer_order_descriptions';

    protected $fillable = ['site_id', 'customer_order_id', 'item_name', 'variation_name', 'quantity', 'price', 'note', 'total', 'is_variation', 'created_by', 'updated_by'];

    public function customerOrder()
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id', 'order_id');
    }

    public function variationItems()
    {
        return $this->hasMany(VariationItem::class, 'customer_order_description_id');
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('customer_order_description')
            ->setDescriptionForEvent(fn(string $eventName) => "Order description for customer order with ID {$this->customer_order_id} has been {$eventName}");
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->properties = $activity->properties->merge([
            'attributes' => $this->getAttributes(),
        ]);
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->is_variation) {
                // Sum totals from variation items (must be loaded or already saved)
                $model->total = $model->variationItems()->sum('total');
            } else {
                $model->total = $model->quantity * $model->price;
            }
        });

        static::created(function ($model) {
            $model->customerOrder?->recalculateGrandTotal();
        });

        static::updated(function ($model) {
            $model->customerOrder?->recalculateGrandTotal();
        });

        static::deleted(function ($model) {
            $model->customerOrder?->recalculateGrandTotal();
        });

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


}