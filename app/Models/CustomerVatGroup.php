<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerVatGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_vat_groups';

    protected $fillable = [
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

    public function customers()
    {
        return $this->hasMany(CustomerVatGroupCustomer::class);
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
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
