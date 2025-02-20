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
        'sample_order_item_id',
        'variation_name',
        'quantity',
        'price',
        'total',
    ];

    public function sampleOrderItem()
    {
        return $this->belongsTo(SampleOrderItem::class);
    }

    // Automatically calculate the total before saving
    protected static function booted()
    {
        static::saving(function ($model) {
            $model->total = $model->quantity * $model->price;
        });
    }
}
