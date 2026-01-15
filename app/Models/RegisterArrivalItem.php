<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegisterArrivalItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'site_id',
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

    public function getTotalAttribute()
    {
        return $this->quantity * $this->price;
    }

    protected static function booted()
    {
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