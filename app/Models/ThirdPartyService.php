<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ThirdPartyService extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['supplier_id'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function processes()
    {
        return $this->hasMany(ThirdPartyServiceProcess::class);
    }
}