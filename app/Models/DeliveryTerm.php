<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryTerm extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'created_by', 'updated_by'];

    // Relationship example: many POs can have one delivery term
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function requestForQuotations()
    {
        return $this->hasMany(RequestForQuotation::class);
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
}
