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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'purchase_order_id',
                'location_id',
                'received_date',
                'invoice_number',
                'note',
            ])
            ->useLogName('register_arrival')
            ->setDescriptionForEvent(function (string $eventName) {
                $description = "Register Arrival {$this->id} has been {$eventName}";

                // Check if the related PurchaseOrder exists before accessing its properties
                if ($this->purchaseOrder) {
                    $description .= " (Purchase Order ID: {$this->purchase_order_id}, ";
                    $description .= "Provider Type: {$this->purchaseOrder->provider_type}, ";
                    $description .= "Provider ID: {$this->purchaseOrder->provider_id})";
                } else {
                    $description .= " (Purchase Order ID: {$this->purchase_order_id}, ";
                    $description .= "Provider Type: N/A, ";
                    $description .= "Provider ID: N/A)";
                }

                return $description;
            });
    }
}