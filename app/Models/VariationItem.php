<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_order_description_id',
        'item_name',
        'quantity',
        'price',
        'total',
    ];

    // Relationship to the CustomerOrderDescription model
    public function customerOrderDescription()
    {
        return $this->belongsTo(CustomerOrderDescription::class);
    }
}
