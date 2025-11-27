<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionSetupAccount extends Model
{
    use SoftDeletes;

    protected $table = 'transaction_setup_accounts';

    protected $fillable = [
        'transaction_setup_id',
        'debit_account_id_1',
        'debit_account_id_2',
        'debit_account_id_3',
        'debit_account_id_4',
        'debit_account_id_5',
        'credit_account_id_1',
        'credit_account_id_2',
        'credit_account_id_3',
        'credit_account_id_4',
        'credit_account_id_5',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Relationships
     */

    // Belongs to a Transaction Setup
    public function setup()
    {
        return $this->belongsTo(TransactionSetup::class, 'transaction_setup_id');
    }

    // Optional: if you have an Accounts model
    public function debitAccount()
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
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
