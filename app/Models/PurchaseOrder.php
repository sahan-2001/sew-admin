<?php
// filepath: /E:/Academics/final Project/New folder/22222/app/Models/PurchaseOrder.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'provider_type',
        'provider_id',
        'provider_name',
        'provider_email',
        'provider_phone',
        'wanted_date',
        'special_note',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function provider()
    {
        if ($this->provider_type === 'supplier') {
            return $this->belongsTo(Supplier::class, 'provider_id', 'supplier_id');
        } elseif ($this->provider_type === 'customer') {
            return $this->belongsTo(Customer::class, 'provider_id', 'customer_id');
        }
        return null;
    }
}