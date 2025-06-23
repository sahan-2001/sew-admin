<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Workstation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'production_line_id',
        'name',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function operations()
    {
        return $this->hasMany(Operation::class);
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'production_line_id',
                'name',
                'description',
                'status',
                'created_by',
                'updated_by',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('workstation')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = auth()->user();
                $userInfo = $user ? " by {$user->name} (ID: {$user->id})" : "";
                return "Workstation #{$this->id} has been {$eventName}{$userInfo}";
            });
    }
}
