<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialQC extends Model
{
    use HasFactory;

    protected $table = 'material_qcs';

    protected $fillable = ['purchase_order_id', 'status'];

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
}