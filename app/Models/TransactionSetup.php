<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionSetup extends Model
{
    use SoftDeletes;

    protected $table = 'transaction_setups';

    protected $fillable = [
        'transaction_name',
        'description',
        'remarks',
        'status',
        'created_by',
        'updated_by',
    ];

    public function transactionSetupAccounts()
    {
        return $this->hasMany(TransactionSetupAccount::class);
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
