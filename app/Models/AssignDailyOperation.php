<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class AssignDailyOperation extends Model
{
    use SoftDeletes;

    protected $fillable = ['order_type', 'order_id', 'created_by', 'updated_by', 'operation_date'];
    

    public function lines()
    {
        return $this->hasMany(AssignDailyOperationLine::class);
    }

    public function assignedWorkingHours()
    {
        return $this->hasMany(AssignedWorkingHour::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(CuttingLabel::class, 'assign_daily_operation_labels')
            ->using(AssignDailyOperationLabel::class)
            ->withTimestamps()
            ->withPivot('id', 'deleted_at'); 
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