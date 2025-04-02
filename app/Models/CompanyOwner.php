<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CompanyOwner extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id', 'name', 'address_line_1', 'address_line_2', 'address_line_3',
        'city', 'postal_code', 'country', 'phone_1', 'phone_2',
        'email', 'joined_date', 'updated_by'
    ];

    protected $casts = [
        'joined_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone_1', 'email'])
            ->setDescriptionForEvent(function(string $eventName) {
                $updaterEmail = $this->updater->email ?? 'system';
                return "Owner details {$eventName} by {$updaterEmail}";
            })
            ->useLogName('company_owner');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}