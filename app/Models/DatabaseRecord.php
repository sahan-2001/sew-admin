<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseRecord extends Model
{
    protected $guarded = [];
    public $timestamps = false;

    protected $fillable = [
        'created_by',
        'updated_by',
    ];

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