<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegisterArrival extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'location_id',
        'received_date',
        'invoice_number',
        'image_of_invoice',
        'note'
    ];

    public function items()
    {
        return $this->hasMany(RegisterArrivalItem::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function location()
    {
        return $this->belongsTo(InventoryLocation::class);
    }
}