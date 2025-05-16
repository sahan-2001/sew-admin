<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssignedThirdPartyService extends Model
{
    use SoftDeletes;

    protected $fillable = ['third_party_service_id', 'assign_daily_operation_line_id', 'created_by', 'updated_by',];

    public function line()
    {
        return $this->belongsTo(AssignDailyOperationLine::class, 'assign_daily_operation_line_id');
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