<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class RequestForQuotationItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'request_for_quotation_id',
        'inventory_item_id',
        'quantity',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = Auth::id();
            $model->updated_by = Auth::id();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });
    }

    /* -----------------------
     | RELATIONSHIPS
     ----------------------- */
    public function requestForQuotation()
    {
        return $this->belongsTo(RequestForQuotation::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
