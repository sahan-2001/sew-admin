<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'item_code',
        'name',
        'category_id',
        'special_note',
        'uom',
        'available_quantity',
        'moq',
        'max_order_quantity',
        'inventory_item_vat_group_id',
        'created_by',
        'updated_by',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function vatGroup()
    {
        return $this->belongsTo(InventoryItemVatGroup::class, 'inventory_item_vat_group_id');
    }
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Ensure category_id can still be null
            $model->category_id = $model->category_id ?? null;

            // Always use fallback prefix for item_code
            $model->item_code = self::generateUniqueItemCode('ITEM');

            // Set created_by / updated_by
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    /**
     * Generate a unique item code based on category
     */
    protected static function generateUniqueItemCode(string $prefix): string
    {
        $prefix = strtoupper(substr($prefix, 0, 4)); // "ITEM"
        $lastItem = self::where('item_code', 'like', $prefix.'%')
                        ->orderBy('item_code', 'desc')
                        ->first();

        $nextNumber = 1;
        if ($lastItem) {
            $numericPart = (int) substr($lastItem->item_code, strlen($prefix));
            $nextNumber = $numericPart + 1;
        }

        return $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    protected static $logAttributes = [
        'item_code',
        'name',
        'category_id',
        'special_note',
        'uom',
        'available_quantity',
        'inventory_item_vat_group_id',
        'created_by',
    ];

    protected static $logName = 'inventory_item';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'item_code',
                'name',
                'category_id',
                'special_note',
                'uom',
                'available_quantity',
                'created_by', 
            ])
            ->useLogName('inventory_item')
            ->setDescriptionForEvent(fn(string $eventName) => "Inventory Item {$this->id} has been {$eventName}");
    }

    public function registerArrivalItems()
    {
        return $this->hasMany(RegisterArrivalItem::class, 'item_id');
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