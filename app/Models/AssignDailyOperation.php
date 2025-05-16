<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssignDailyOperation extends Model
{
    use SoftDeletes;

    protected $fillable = ['order_type', 'order_id', 'created_by', 'updated_by'];

    public function lines()
    {
        return $this->hasMany(AssignDailyOperationLine::class);
    }

    public function assignedWorkingHours()
    {
        return $this->hasMany(AssignedWorkingHour::class);
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