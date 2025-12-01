<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAssetControlAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'asset_category',
        'cost',
        'accumulated_depreciation',
        'net_book_value',
        'debit_total_vat',
        'credit_total_vat',
        'balance_vat',
        'is_active',
    ];
}
