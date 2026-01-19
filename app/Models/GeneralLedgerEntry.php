<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class GeneralLedgerEntry extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'site_id',
        'entry_code',
        'account_id',
        'source_table',
        'source_id',
        'reference_table',
        'reference_record_id',
        'entry_date',
        'debit',
        'credit',
        'transaction_name',
        'description',
        'created_by',
        'updated_by',
    ];

    protected static $logName = 'general_ledger_entry';

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('general_ledger_entry')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = auth()->user();
                $userInfo = $user ? " by {$user->name} (ID: {$user->id})" : "";
                return "GL Entry [{$this->entry_code}] was {$eventName}{$userInfo}";
            });
    }

    /**
     * Relationships
     */
    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getAccountLabelAttribute()
    {
        return "{$this->control_account_code} - {$this->control_account_name}";
    }


    /**
     * Automatically assign created_by and updated_by
     */
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
    }

    /**
     * Helper Accessors
     */
    public function getFormattedDebitAttribute(): string
    {
        return 'LKR ' . number_format($this->debit, 2);
    }

    public function getFormattedCreditAttribute(): string
    {
        return 'LKR ' . number_format($this->credit, 2);
    }

    public function getFormattedBalanceAttribute(): string
    {
        return 'LKR ' . number_format($this->debit - $this->credit, 2);
    }
}
