<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryItemVatGroup extends Model
{
    use HasFactory, SoftDeletes;

    // IMPORTANT: because of trailing underscore
    protected $table = 'inventory_item_vat_groups';

    protected $fillable = [
        'code',
        'vat_group_name',
        'vat_rate',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'vat_rate' => 'decimal:2',
    ];

    /* ---------------------------------
     | Relationships
     |----------------------------------*/

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'inventory_item_vat_group_id', 'id');
    }

    /* ---------------------------------
     | Scopes
     |----------------------------------*/

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /* ---------------------------------
     | Model Events (Auto User Tracking)
     |----------------------------------*/

    protected static function booted()
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
