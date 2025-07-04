<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

class SampleOrderItem extends Model
{
    use LogsActivity;

    // The table associated with the model.
    protected $table = 'sample_order_items';

    // Define fields that should be mass assignable
    protected $fillable = [
        'sample_order_id',
        'item_name',
        'variation_name',
        'quantity',
        'price',
        'note',
        'total',
        'is_variation',
        'created_by',
        'updated_by',
    ];

    public function sampleOrder()
    {
        return $this->belongsTo(SampleOrder::class, 'sample_order_id', 'order_id');
    }

    public function variations()
    {
        return $this->hasMany(SampleOrderVariation::class, 'sample_order_item_id');
    }

    // Configure activity log options
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('sample_order_item')
            ->setDescriptionForEvent(fn(string $eventName) => "Order item for sample order with ID {$this->sample_order_id} has been {$eventName}");
    }

    // Override the tapActivity method to include additional properties
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
                $model->total = $model->variations()->sum('total');
            } else {
                $model->total = $model->quantity * $model->price;
            }
        });

        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });

        static::created(function ($model) {
            $model->sampleOrder?->recalculateGrandTotal();
        });

        static::updated(function ($model) {
            $model->sampleOrder?->recalculateGrandTotal();
        });

        static::deleted(function ($model) {
            $model->sampleOrder?->recalculateGrandTotal();
        });
    }
}
