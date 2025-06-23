<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class NonInventoryCategory extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'created_by',
    ];

    protected static $logAttributes = [
        'name',
        'created_by',
    ];

    protected static $logName = 'non_inventory_category';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'created_by'])
            ->useLogName('non_inventory_category')
            ->setDescriptionForEvent(fn(string $eventName) => 
                "NonInventoryCategory #{$this->id} has been {$eventName}"
            );
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function nonInventoryItems()
    {
        return $this->hasMany(NonInventoryItem::class);
    }

    
}
