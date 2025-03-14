<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'item_code',
        'name',
        'category',
        'special_note',
        'uom',
        'available_quantity',
        'created_by', // Add created_by to fillable fields
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $lastItem = self::latest()->first();
            $nextId = $lastItem ? $lastItem->id + 1 : 1;
            $categoryCode = strtoupper(substr($model->category, 0, 3)); // First 3 letters of the category
            $model->item_code = $categoryCode . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            $model->created_by = auth()->id(); // Set the created_by field to the current user's ID
        });
    }

    protected static $logAttributes = [
        'item_code',
        'name',
        'category',
        'special_note',
        'uom',
        'available_quantity',
        'created_by', // Log the created_by field
    ];

    protected static $logName = 'inventory_item';

    /**
     * Get the options for activity logging.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'item_code',
                'name',
                'category',
                'special_note',
                'uom',
                'available_quantity',
                'created_by', // Log the created_by field
            ])
            ->useLogName('inventory_item')
            ->setDescriptionForEvent(fn(string $eventName) => "Inventory Item {$this->id} has been {$eventName} by User " . ($this->createdBy ? $this->createdBy->email : 'Unknown'));
    }

    // Define the relationship to the User model
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registerArrivalItems()
    {
        return $this->hasMany(RegisterArrivalItem::class, 'item_id');
    }
}