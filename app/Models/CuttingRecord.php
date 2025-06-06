<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuttingRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
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

    public function wasteRecords()
    {
        return $this->hasMany(CuttingInventoryWaste::class);
    }

    public function nonInventoryWaste()
    {
        return $this->hasMany(CuttingNonInventoryWaste::class);
    }

    public function byProductRecords()
    {
        return $this->hasMany(CuttingByProduct::class);
    }

    public function cutPieceLabels()
    {
        return $this->hasMany(CuttingLabel::class);
    }

    public function orderItems()
    {
        return $this->hasMany(CuttingOrderItem::class);
    }

    public function orderVariations()
    {
        return $this->hasMany(CuttingOrderVariation::class);
    }

    public function cutMaterials()
    {
        return $this->hasMany(ReleaseMaterial::class);
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
}