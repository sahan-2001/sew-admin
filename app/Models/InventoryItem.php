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

    protected static $logAttributes = [
        'item_code',
        'name',
        'category',
        'special_note',
        'uom',
        'available_quantity',
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
            ])
            ->useLogName('inventory_item')
            ->setDescriptionForEvent(fn(string $eventName) => "Inventory Item {$this->id} has been {$eventName} by User {$this->user_id} ({$this->user->email})");
    }
}