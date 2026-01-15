<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Company extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'site_id', 'name', 'address_line_1', 'address_line_2', 'address_line_3',
        'city', 'postal_code', 'country', 'primary_phone', 'secondary_phone',
        'email', 'started_date', 'special_notes', 'updated_by',
    ];

    protected $casts = [
        'started_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'primary_phone', 'city'])
            ->setDescriptionForEvent(function(string $eventName) {
                $updaterEmail = $this->updater->email ?? 'system';
                return "Company {$eventName} by {$updaterEmail}";
            })
            ->useLogName('company');
    }

    public function owner()
    {
        return $this->hasOne(CompanyOwner::class);
    }

    public function management()
    {
        return $this->hasMany(CompanyManagement::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
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
}