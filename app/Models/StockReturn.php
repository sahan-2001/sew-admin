<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockReturn extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'stock_id',
        'item_id',
        'location_id',
        'purchase_order_id',
        'provider_type',
        'provider_id',
        'quantity',
        'cost',
        'reason',
        'created_by',
        'updated_by',
    ];

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function location()
    {
        return $this->belongsTo(InventoryLocation::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Optional: polymorphic relation if you need dynamic provider
    public function provider()
    {
        return $this->morphTo(__FUNCTION__, 'provider_type', 'provider_id');
    }

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('stock_return')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = auth()->user();
                $userInfo = $user ? " by {$user->name} (ID: {$user->id})" : "";
                return "StockReturn #{$this->id} has been {$eventName}{$userInfo}";
            });
    }
}
