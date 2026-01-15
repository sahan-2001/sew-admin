<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThirdPartyServiceProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'third_party_service_id',
        'description',
        'related_table',
        'related_record_id',
        'unit_of_measurement',
        'amount',
        'remaining_amount',
        'used_amount',
        'unit_rate',
        'total',
        'payable_balance',
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
    
        static::created(function ($model) {
            $model->thirdPartyService?->updateServiceTotal();
        });

        static::updated(function ($model) {
            $model->thirdPartyService?->updateServiceTotal();
        });

        static::deleted(function ($model) {
            $model->thirdPartyService?->updateServiceTotal();
        });
    }

}