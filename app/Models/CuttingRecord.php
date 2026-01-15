<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CuttingRecord extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'site_id',
        'order_type',
        'order_id',
        'cutting_station_id',
        'release_material_id',
        'operation_date',
        'operated_time_from',
        'operated_time_to',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $logName = 'cutting_record';

    public function cuttingStation(): BelongsTo
    {
        return $this->belongsTo(CuttingStation::class);
    }

    public function releaseMaterial(): BelongsTo
    {
        return $this->belongsTo(ReleaseMaterial::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(CuttingEmployee::class);
    }

    public function qualityControls(): HasMany
    {
        return $this->hasMany(CuttingQualityControl::class);
    }

    public function wasteRecords(): HasMany
    {
        return $this->hasMany(CuttingInventoryWaste::class);
    }

    public function nonInventoryWaste(): HasMany
    {
        return $this->hasMany(CuttingNonInventoryWaste::class);
    }

    public function byProductRecords(): HasMany
    {
        return $this->hasMany(CuttingByProduct::class);
    }

    public function cutPieceLabels(): HasMany
    {
        return $this->hasMany(CuttingLabel::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(CuttingOrderItem::class);
    }

    public function orderVariations(): HasMany
    {
        return $this->hasMany(CuttingOrderVariation::class);
    }

    public function cutMaterials(): HasMany
    {
        return $this->hasMany(ReleaseMaterial::class);
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('cutting_record')
            ->setDescriptionForEvent(function (string $eventName) {
                $changes = $this->getDirty();
                $description = "Cutting Record #{$this->id} was {$eventName}";

                if ($eventName === 'updated' && !empty($changes)) {
                    $description .= ". Changes: " . json_encode($changes);
                }

                $user = auth()->user();
                if ($user) {
                    $description .= " by {$user->name}";
                }

                return $description;
            });
    }
}
