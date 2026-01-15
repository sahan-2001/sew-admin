<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SampleOrderVariation extends Model
{
    use HasFactory;

    // The table associated with the model
    protected $table = 'sample_order_variations';

    // Define fields that should be mass assignable
    protected $fillable = [
        'site_id',
        'sample_order_item_id',
        'variation_name',
        'quantity',
        'price',
        'total',
        'created_by',
        'updated_by',
    ];

    public function sampleOrderItem()
    {
        return $this->belongsTo(SampleOrderItem::class);
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            $model->total = $model->quantity * $model->price;
        });

        static::created(function ($model) {
            $model->sampleOrderItem?->save(); 
        });

        static::updated(function ($model) {
            $model->sampleOrderItem?->save(); 
        });

        static::deleted(function ($model) {
            $model->sampleOrderItem?->save(); 
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
