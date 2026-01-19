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
        'site_id',
        'code',
        'name',
        'description',
        'vat_account_type',
        'status',
        'debit_total_vat',
        'credit_total_vat',
        'balance_vat',
        'created_by',
        'updated_by',
    ];

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

        static::saving(function ($model) {
            // 🔒 ALWAYS calculate balance_vat
            $model->balance_vat =
                ($model->credit_total_vat ?? 0) - ($model->debit_total_vat ?? 0);
        });
    }
}
