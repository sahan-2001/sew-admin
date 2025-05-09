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

    // Defining the fillable properties to allow mass assignment
    protected $fillable = [
        'name',
        'warehouse_id',
        'location_type',
        'capacity',
        'measurement_unit',
        'created_by',
    ];

    // Defining the date attributes for soft deletes
    protected $dates = ['deleted_at'];

    /**
     * Get the warehouse that owns the inventory location.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class, 'location_id');
    }

    /**
     * Get the user that created the inventory location.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static $logAttributes = ['name', 'warehouse_id', 'location_type', 'capacity', 'measurement_unit'];
    protected static $logName = 'inventory_location';

    /**
     * Get the options for activity logging.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'warehouse_id', 'location_type', 'capacity', 'measurement_unit'])
            ->useLogName('inventory_location')
            ->setDescriptionForEvent(fn(string $eventName) => "Inventory Location {$this->id} has been {$eventName} by User {$this->created_by} ({$this->creator->email})");
    }
}