<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class InventoryLocation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'warehouse_id',
        'location_type',
        'capacity',
        'measurement_unit',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $dates = ['deleted_at'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'location_id');
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

    protected static $logAttributes = ['name', 'warehouse_id', 'location_type', 'capacity', 'measurement_unit'];
    protected static $logName = 'inventory_location';

    /**
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'warehouse_id', 'location_type', 'capacity', 'measurement_unit'])
            ->useLogName('inventory_location')
            ->setDescriptionForEvent(fn(string $eventName) => "Inventory Location {$this->id} has been {$eventName} ");
    }
}