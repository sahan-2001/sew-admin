<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CuttingRecordEmployee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['cutting_record_id', 'user_id', 'created_by', 'updated_by'];

    public function cuttingRecord()
    {
        return $this->belongsTo(CuttingRecord::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
