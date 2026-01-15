<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuttingQualityControl extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'site_id',
        'cutting_record_id',
        'qc_user_id',
        'inspected_quantity',
        'accepted_quantity',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $appends = ['rejected_quantity'];

    public function getRejectedQuantityAttribute()
    {
        return $this->inspected_quantity - $this->accepted_quantity;
    }

    public function cuttingRecord(): BelongsTo
    {
        return $this->belongsTo(CuttingRecord::class);
    }

    public function qcUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qc_user_id');
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
}