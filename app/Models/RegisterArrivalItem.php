<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegisterArrivalItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'register_arrival_id',
        'item_id',
        'quantity',
        'price',
        'total'
    ];

    public function registerArrival()
    {
        return $this->belongsTo(RegisterArrival::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(RegisterArrival::class, 'register_arrival_id');

    }
}