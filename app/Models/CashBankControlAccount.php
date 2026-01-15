<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CashBankControlAccount extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'site_id',
        'code',
        'name',
        'account_type',        // Cash, Bank, etc.
        'currency',
        'opening_balance',
        'opening_balance_date',
        'debit_balance',
        'credit_balance',
        'balance',
        'bank_name',
        'branch_name',
        'account_number',
        'swift_code',
        'iban',
        'bank_address',
        'tax_number',
        'is_active',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected static $logName = 'cash_bank_control_account';

    /**
     * Activity Log Options
     */
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
                return "Cash/Bank Control Account [{$this->code} - {$this->name}] was {$eventName}{$userInfo}";
            });
    }

    // 🔹 Optional: link to transactions (if you create a Transaction model)
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'cash_bank_control_account_id');
    }

    /**
     * Automatically assign created_by and updated_by users
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
    public function getFormattedBalanceAttribute(): string
    {
        return ($this->currency ?? 'LKR') . ' ' . number_format($this->current_balance, 2);
    }

    public function getFormattedBalanceVatAttribute(): string
    {
        return ($this->currency ?? 'LKR') . ' ' . number_format($this->balance_vat, 2);
    }
}
