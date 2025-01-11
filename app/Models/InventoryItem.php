<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_code',
        'name',
        'category',
        'special_note',
        'uom',
        'available_quantity',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $lastItem = self::latest()->first();
            $nextId = $lastItem ? $lastItem->id + 1 : 1;
            $categoryCode = strtoupper(substr($model->category, 0, 3)); // First 3 letters of the category
            $model->item_code = $categoryCode . str_pad($nextId, 4, '0', STR_PAD_LEFT);
        });
    }
}