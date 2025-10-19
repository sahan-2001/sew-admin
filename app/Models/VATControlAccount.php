<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VATControlAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'v_a_t_control_accounts';

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'vat_percentage',
        'debit_total_vat',
        'credit_total_vat',
        'balance_vat',
        'created_by',
        'updated_by',
    ];

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
