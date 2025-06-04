<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuttingNonInventoryWaste extends Model
{
    use SoftDeletes;

    protected $table = 'cutting_non_inventory_waste'; 

    protected $fillable = [
        'cutting_record_id',
        'item_id',
        'amount',
        'unit',
        'created_by',
        'updated_by',
    ];

    public function cuttingRecord(): BelongsTo
    {
        return $this->belongsTo(CuttingRecord::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(NonInventoryItem::class);
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