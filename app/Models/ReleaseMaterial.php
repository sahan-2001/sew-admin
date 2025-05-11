<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReleaseMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_type',
        'order_id',
        'production_line_id',
        'workstation_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function workstation()
    {
        return $this->belongsTo(Workstation::class);
    }

    public function lines()
    {
        return $this->hasMany(ReleaseMaterialLine::class);
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