<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class NonInventoryItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'item_id',
        'name',
        'non_inventory_category_id',
        'price',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected static $logAttributes = [
        'item_id',
        'name',
        'non_inventory_category_id',
        'price',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected static $logName = 'non_inventory_item';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->useLogName('non_inventory_item')
            ->setDescriptionForEvent(fn(string $eventName) => "NonInventoryItem has been {$eventName}");
    }

    public function category()
    {
        return $this->belongsTo(NonInventoryCategory::class, 'non_inventory_category_id');
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
