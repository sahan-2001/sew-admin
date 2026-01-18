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
        'site_id',
        'code',
        'name',
        'account_type',
        'is_control_account',
        'control_account_type',
        'statement_type',
        'sub_category',
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
    protected $guarded = ['balance', 'balance_vat'];

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
        static::saving(function ($model) {

            // Set site_id once
            if (!$model->site_id && session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            // Audit fields
            if (auth()->check()) {
                $model->updated_by = auth()->id();

                if (!$model->exists) {
                    $model->created_by = auth()->id();
                }
            }

            // -----------------------------
            // Balance calculation by type
            // -----------------------------
            $debit  = $model->debit_total ?? 0;
            $credit = $model->credit_total ?? 0;

            $debitVat  = $model->debit_total_vat ?? 0;
            $creditVat = $model->credit_total_vat ?? 0;

            switch ($model->account_type) {
                case 'asset':
                case 'expense':
                    $model->balance     = $debit - $credit;
                    $model->balance_vat = $debitVat - $creditVat;
                    break;

                case 'liability':
                case 'equity':
                case 'income':
                    $model->balance     = $credit - $debit;
                    $model->balance_vat = $creditVat - $debitVat;
                    break;

                default:
                    // Safe fallback
                    $model->balance     = $debit - $credit;
                    $model->balance_vat = $debitVat - $creditVat;
            }
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
