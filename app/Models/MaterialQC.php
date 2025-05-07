<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialQC extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'material_qcs';

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'inspected_quantity',
        'returned_qty',
        'scrapped_qty',
        'cost_of_item',
        'inspected_by',
        'created_by',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(MaterialQCItem::class, 'material_qc_id'); // Ensure the foreign key is correct
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
    
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id'); // Ensure the foreign key is correct
    }

    public function inspectedBy()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}