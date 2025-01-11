<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class SupplierRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'shop_name',
        'address',
        'email',
        'phone_1',
        'phone_2',
        'outstanding_balance',
        'requested_by',
        'approved_by',
        'note',
        'status',
    ];

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}