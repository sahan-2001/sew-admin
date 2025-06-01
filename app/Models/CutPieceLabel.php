<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CutPieceLabel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_type', 'order_id', 'cutting_record_id',
        'number_of_pieces', 'label_start', 'label_end',
        'created_by', 'updated_by'
    ];

    public function cuttingRecord()
    {
        return $this->belongsTo(CuttingRecord::class);
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