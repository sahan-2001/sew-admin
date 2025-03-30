<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RegisterArrival extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

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

<<<<<<< Updated upstream
=======
    /**
     * Configure activity logging options.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
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
=======
                return "Register Arrival record (ID: {$this->id}, Purchase Order ID: {$this->purchase_order_id}) has been {$eventName}.";
>>>>>>> Stashed changes
            });
    }
}