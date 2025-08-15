<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AssignDailyOperation extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = ['order_type', 'order_id', 'created_by', 'status', 'updated_by', 'operation_date'];
    
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $logName = 'assign_daily_operation';

    // Add this to automatically update timestamps when lines change
    protected $touches = ['lines'];

    public function lines()
    {
        return $this->hasMany(AssignDailyOperationLine::class);
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
            
            // Force logging even if only updated_by changed
            if (count($model->getDirty()) === 1 && array_key_exists('updated_by', $model->getDirty())) {
                $model->activitylogOptions->submitEmptyLogs = true;
            }
        });

        // Add this to log when lines are changed
        static::updated(function ($model) {
            if ($model->lines->isDirty()) {
                activity()
                    ->performedOn($model)
                    ->causedBy(auth()->user())
                    ->log("Operation lines were modified");
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('assign_daily_operation')
            ->setDescriptionForEvent(function(string $eventName) {
                $changes = $this->getDirty();
                $description = "Daily Operation #{$this->id} was {$eventName}";
                
                if ($eventName === 'updated') {
                    $description .= ". Changes: " . json_encode($changes);
                    
                    // Check if lines were modified
                    if ($this->lines->isDirty()) {
                        $description .= " (including line modifications)";
                    }
                }
                
                $user = auth()->user();
                if ($user) {
                    $description .= " by {$user->name} (ID: {$user->id})";
                }
                
                return $description;
            });
    }
}