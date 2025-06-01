<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CuttingRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cutting_station_id', 'date', 'time_from', 'time_to',
        'order_type', 'order_id', 'release_material_line_id',
        'waste', 'waste_item_id', 'waste_item_location_id',
        'by_product_amount', 'by_product_id', 'by_product_location_id',
        'created_by', 'updated_by'
    ];

    public function station()
    {
        return $this->belongsTo(CuttingStation::class, 'cutting_station_id');
    }

    public function employees()
    {
        return $this->hasMany(CuttingRecordEmployee::class);
    }

    public function pieceLabels()
    {
        return $this->hasMany(CutPieceLabel::class);
    }

    public function qualityControls()
    {
        return $this->hasMany(CuttingQCRecord::class);
    }

    public function sampleOrderItems()
    {
        return $this->hasMany(SampleOrderItem::class);
    }

    public function customerOrderDescriptions()
    {
        return $this->hasMany(CustomerOrderDescription::class);
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
