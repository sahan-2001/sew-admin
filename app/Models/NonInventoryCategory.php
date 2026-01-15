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
        'site_id',
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

    protected static function booted()
    {
        static::creating(function ($model) {
            // Set site_id from session
            if (session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    public function nonInventoryItems()
    {
        return $this->hasMany(NonInventoryItem::class);
    }

    
}
