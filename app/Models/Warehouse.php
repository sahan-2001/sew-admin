<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    // Defining the fillable properties to allow mass assignment
    protected $fillable = [
        'name',
        'address_line_1',
        'address_line_2',
        'address_line_3',
        'city',
        'note',
        'capacity_length',
        'capacity_width',
        'capacity_height',
        'measurement_unit',
        'user_id',
    ];

    // Defining the date attributes for soft deletes
    protected $dates = ['deleted_at'];

    /**
     * Get the user that owns the warehouse.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the warehouse's full address as a string.
     */
    public function getFullAddressAttribute()
    {
        return $this->address_line_1 . ', ' .
               ($this->address_line_2 ? $this->address_line_2 . ', ' : '') .
               ($this->address_line_3 ? $this->address_line_3 . ', ' : '') .
               $this->city;
    }

    /**
     * Get the warehouse's capacity in the desired unit.
     */
    public function getCapacityAttribute()
    {
        return [
            'length' => $this->capacity_length,
            'width' => $this->capacity_width,
            'height' => $this->capacity_height,
            'measurement_unit' => $this->measurement_unit,
        ];
    }
}
