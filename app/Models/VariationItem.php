<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_order_description_id',
        'variation_name',
        'quantity',
        'price',
        'total',
        'created_by',
        'updated_by',
    ];

    public function customerOrderDescription()
    {
        return $this->belongsTo(CustomerOrderDescription::class, 'customer_order_description_id');
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            $model->total = $model->quantity * $model->price;
        });

        static::created(function ($model) {
            $model->customerOrderDescription?->save(); 
        });

        static::updated(function ($model) {
            $model->customerOrderDescription?->save(); 
        });

        static::deleted(function ($model) {
            $model->customerOrderDescription?->save(); 
        });

        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

}