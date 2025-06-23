<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CuttingStation extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = ['name', 'description', 'created_by', 'updated_by'];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $logName = 'cutting_station';

    public function cuttingRecords()
    {
        return $this->hasMany(CuttingRecord::class);
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
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('cutting_station')
            ->setDescriptionForEvent(function (string $eventName) {
                $changes = $this->getDirty();
                $description = "Cutting Station #{$this->id} was {$eventName}";

                if ($eventName === 'updated' && !empty($changes)) {
                    $description .= ". Changes: " . json_encode($changes);
                }

                $user = auth()->user();
                if ($user) {
                    $description .= " by {$user->name} (ID: {$user->id})";
                }

                return $description;
            });
    }
}
