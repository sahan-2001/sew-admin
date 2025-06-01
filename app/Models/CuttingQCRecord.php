<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuttingQCRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'cutting_record_id', 'qc_type', 'qc_value', 'qc_status',
        'remarks', 'created_by', 'updated_by'
    ];

    public function cuttingRecord()
    {
        return $this->belongsTo(CuttingRecord::class);
    }

    public function qcUser()
    {
        return $this->belongsTo(User::class, 'qc_user_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
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