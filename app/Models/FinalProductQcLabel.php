<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinalProductQCLabel extends Model
{
    protected $table = 'final_product_qc_labels';

    protected $fillable = [
        'final_product_qc_id',
        'cutting_label_id',
        'result',
        'created_by',
        'updated_by',
    ];

    public function finalProductQC()
    {
        return $this->belongsTo(FinalProductQC::class);
    }

    public function cuttingLabel()
    {
        return $this->belongsTo(CuttingLabel::class);
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