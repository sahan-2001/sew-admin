<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashBankControlAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'debit_total_vat',
        'credit_total_vat',
        'balance_vat',
        'bank_name',
        'account_number',
        'account_type',
        'is_active',
    ];
}
