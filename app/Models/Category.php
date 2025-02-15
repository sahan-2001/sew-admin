<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Category extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['name', 'created_by']; // Add created_by to fillable fields

    protected static $logAttributes = ['name', 'created_by']; // Log the created_by field
    protected static $logName = 'category';

    /**
     * Get the options for activity logging.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'created_by']) // Log the created_by field
            ->useLogName('category')
            ->setDescriptionForEvent(fn(string $eventName) => "Category {$this->id} has been {$eventName} by User " . ($this->createdBy ? $this->createdBy->email : 'Unknown'));
    }

    // Define the relationship to the User model
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->id(); // Set the created_by field to the current user's ID
        });
    }
}