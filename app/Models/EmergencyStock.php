<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmergencyStock extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_id',
        'location_id',
        'quantity',
        'cost',
        'updated_date',
        'received_date',
        'created_by',
        'updated_by',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_by = auth()->id();
        });

        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function location()
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
