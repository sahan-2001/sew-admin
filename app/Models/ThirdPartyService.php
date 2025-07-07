<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ThirdPartyService extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = ['supplier_id', 'name', 'service_total', 'paid', 'remaining_balance','status', 'created_by', 'updated_by'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function processes()
    {
        return $this->hasMany(ThirdPartyServiceProcess::class);
    }

    public function payments()
    {
        return $this->hasMany(ThirdPartyServicePayment::class);
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

    public function updateServiceTotal()
    {
        $total = $this->processes()->sum('total');
        $this->update(['service_total' => $total]);
    }

    public function getRemainingBalanceAttribute()
    {
        return $this->service_total - $this->paid;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('third_party_service')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = auth()->user();
                $userInfo = $user ? " by {$user->name} (ID: {$user->id})" : "";
                return "ThirdPartyService #{$this->id} was {$eventName}{$userInfo}";
            });
    }
}
