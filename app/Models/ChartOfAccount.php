<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ChartOfAccount extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'code',
        'name',
        'account_type',
        'is_control_account',
        'control_account_type',
        'statement_type',
        'description',
        'vat_output_account_id',
        'vat_input_account_id',
        'debit_total',
        'credit_total',
        'balance',
        'debit_total_vat',
        'credit_total_vat',
        'balance_vat',
        'status',
        'created_by',
        'updated_by',
    ];

    protected static $logName = 'chart_of_account';

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('chart_of_account')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = auth()->user();
                $userInfo = $user ? " by {$user->name} (ID: {$user->id})" : "";
                return "Chart of Account [{$this->code} - {$this->name}] was {$eventName}{$userInfo}";
            });
    }

    /**
     * Relationships
     */

    public function vatOutputAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'vat_output_account_id');
    }

    public function vatInputAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'vat_input_account_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // 🔹 Related Control Accounts
    public function customerControlAccounts()
    {
        return $this->hasMany(CustomerControlAccount::class, 'chart_of_account_id');
    }

    public function supplierControlAccounts()
    {
        return $this->hasMany(SupplierControlAccount::class, 'chart_of_account_id');
    }

    public function vatControlAccounts()
    {
        return $this->hasMany(VATControlAccount::class, 'chart_of_account_id');
    }

    /**
     * Automatically assign created_by and updated_by users
     */
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

    /**
     * Helper Accessors
     */
    public function getFormattedBalanceAttribute(): string
    {
        return 'LKR ' . number_format($this->balance, 2);
    }

    public function getFormattedBalanceVatAttribute(): string
    {
        return 'LKR ' . number_format($this->balance_vat, 2);
    }
}
