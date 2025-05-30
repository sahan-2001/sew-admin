<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UMOperationLineService extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['third_party_service_id', 'u_m_operation_line_id', 'created_by', 'updated_by',];

    public function line()
    {
        return $this->belongsTo(UMOperationLine::class, 'u_m_operation_line_id');
    }
    
    public function thirdPartyService()
    {
        return $this->belongsTo(ThirdPartyService::class, 'third_party_service_id');
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