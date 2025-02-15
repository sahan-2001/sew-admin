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
    ];

    public function customerOrderDescription()
    {
        return $this->belongsTo(CustomerOrderDescription::class, 'customer_order_description_id');
    }

    // Automatically calculate the total before saving
    protected static function booted()
    {
        static::saving(function ($model) {
            $model->total = $model->quantity * $model->price;
        });
    }
}