<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThirdPartyServiceProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'third_party_service_id',
        'sequence_number',
        'description',
        'related_table',
        'related_record_id',
        'unit_of_measurement',
        'amount',
        'unit_rate',
        'total',
        'outstanding_balance',
    ];

    public function thirdPartyService()
    {
        return $this->belongsTo(ThirdPartyService::class);
    }
}