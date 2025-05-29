<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReleaseMaterialLine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'release_material_id',
        'item_id',
        'stock_id',
        'location_id',
        'quantity',
        'cost',
        'created_by',
        'updated_by',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function releaseMaterial()
    {
        return $this->belongsTo(ReleaseMaterial::class);
    }

    public function location()
    {
        return $this->belongsTo(\App\Models\InventoryLocation::class, 'location_id');
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