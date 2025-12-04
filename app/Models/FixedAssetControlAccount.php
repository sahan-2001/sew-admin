<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FixedAssetControlAccount extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'asset_category',

        'purchasing_cost',
        'additional_cost_1',
        'additional_cost_description_1',
        'additional_cost_2',
        'additional_cost_description_2',
        'additional_cost_3',
        'additional_cost_description_3',
        'total_initial_cost',

        'accumulated_depreciation',
        'net_book_value',

        'debit_balance',
        'credit_balance',
        'net_debit_balance',

        'is_active',
        'status',
        'created_by',
        'updated_by',
    ];

    protected static $logName = 'fixed_asset_control_account';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($this->logName)
            ->setDescriptionForEvent(function (string $eventName) {
                $user = auth()->user();
                $userInfo = $user ? " by {$user->name} (ID: {$user->id})" : "";
                return "Fixed Asset Control Account [{$this->code} - {$this->name}] was {$eventName}{$userInfo}";
            });
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
