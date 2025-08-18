<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinalProductQC extends Model
{
    use SoftDeletes;

    protected $table = 'final_product_qcs'; 

    protected $fillable = [
        'cutting_label_id',
        'status',
        'qc_officer_id',
        'inspected_date',
        'result',
        'created_by',
        'updated_by',
    ];

    public function cuttingLabel()
    {
        return $this->belongsTo(CuttingLabel::class);
    }

    public function qcOfficer()
    {
        return $this->belongsTo(User::class, 'qc_officer_id');
    }

    public function scopeForOrder($query, $orderType, $orderId)
    {
        return $query->where('order_type', $orderType)
                    ->where('order_id', $orderId);
    }

    public function scopeWithStatuses($query, array $statuses)
    {
        return $query->whereIn('status', $statuses);
    }

}