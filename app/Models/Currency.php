<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'code',
        'name',
        'symbol',
        'is_active',
    ];

    // Relations
    public function rfqs()
    {
        return $this->hasMany(RequestForQuotation::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseQuotations()
    {
        return $this->hasMany(PurchaseQuotation::class);
    }

    public function requestForQuotations()
    {
        return $this->hasMany(RequestForQuotation::class, 'currency_code_id', 'id');
    }

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
}
