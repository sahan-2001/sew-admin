<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
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
}
