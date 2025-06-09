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
        'status',
        'total',
        'created_by',
        'updated_by',
    ];

    public function registerArrival()
    {
        return $this->belongsTo(RegisterArrival::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(RegisterArrival::class, 'register_arrival_id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
}