<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MaterialQC extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'material_qcs';

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'inspected_quantity',
        'approved_qty',
        'returned_qty',
        'scrapped_qty',
        'add_returned',
        'add_scrap',
        'total_returned',
        'total_scrap',
        'available_to_store',
        'cost_of_item',
        'store_location_id',
        'register_arrival_id',
        'inspected_by',
        'status',
        'created_by',
        'updated_by',
    ];

    // Spatie logging configuration
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $logName = 'material_qc';

    // Relationships
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id'); 
    }

    public function inspectedBy()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function storeLocation()
    {
        return $this->belongsTo(InventoryLocation::class, 'store_location_id');
    }

    public function registerArrival()
    {
        return $this->belongsTo(RegisterArrival::class, 'register_arrival_id');
    }

    // Auto-fill created_by and updated_by
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

    // Configure logging behavior
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('material_qc')
            ->setDescriptionForEvent(function (string $eventName) {
                $changes = $this->getDirty();
                $description = "Material QC record #{$this->id} was {$eventName}";

                if ($eventName === 'updated' && !empty($changes)) {
                    $description .= ". Changes: " . json_encode($changes);
                }

                if ($user = auth()->user()) {
                    $description .= " by {$user->name} (ID: {$user->id})";
                }

                return $description;
            });
    }
}
