<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThirdPartyServiceProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'third_party_service_id',
        'sequence_number',
        'description',
        'related_table',
        'related_record_id',
        'unit_of_measurement',
        'amount',
        'unit_rate',
        'total',
        'outstanding_balance',
        'created_by',
        'updated_by',
    ];

    public function thirdPartyService()
    {
        return $this->belongsTo(ThirdPartyService::class);
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