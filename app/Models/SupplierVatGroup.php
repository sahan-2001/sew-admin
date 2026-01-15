<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierVatGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'supplier_vat_groups';

    protected $fillable = [
        'site_id',
        'code',
        'vat_group_name',
        'vat_rate',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'vat_rate' => 'decimal:2',
    ];

    /* ---------------------------------
     | Relationships
     |----------------------------------*/

    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'supplier_vat_group_id', 'id');
    }

    /* ---------------------------------
     | Scopes
     |----------------------------------*/

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /* ---------------------------------
     | Model Events (Auto User Tracking)
     |----------------------------------*/

    protected static function booted()
    {
        static::creating(function ($model) {
            // Set site_id from session
            if (session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });
        

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }
}
