<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class CuttingLabel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'site_id',
        'cutting_record_id',
        'order_type',
        'order_id',
        'order_item_id',
        'order_variation_id',
        'quantity',
        'label',
        'status',
        'barcode',
        'barcode_id',
        'created_by',
        'updated_by',
    ];

    public function cuttingRecord(): BelongsTo
    {
        return $this->belongsTo(CuttingRecord::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(CuttingOrderItem::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(CuttingOrderVariation::class, 'order_variation_id');
    }

    public function assignedOperations(): BelongsToMany
    {
        return $this->belongsToMany(AssignDailyOperation::class, 'assign_daily_operation_labels')
            ->using(AssignDailyOperationLabel::class)
            ->withTimestamps()
            ->withPivot('id', 'deleted_at');
    }

    public function getBarcodeUrlAttribute()
    {
        return $this->barcode ? asset($this->barcode) : null;
    }
        
    protected static function booted()
    {
        static::creating(function ($model) {
            // Set site_id from session if column exists
            if (isset($model->site_id) && session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            // Set created_by and updated_by
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }

            // Generate barcode_id if empty
            if (isset($model->barcode_id) && empty($model->barcode_id)) {
                $model->barcode_id = uniqid('CUT');
            }

            // Generate 16-digit random code if needed
            if (isset($model->random_code)) {
                $model->random_code = '';
                for ($i = 0; $i < 16; $i++) {
                    $model->random_code .= mt_rand(0, 9);
                }
            }

            // Calculate balances if applicable
            if (isset($model->debit_total) && isset($model->credit_total)) {
                $model->balance = $model->debit_total - $model->credit_total;
            }

            if (isset($model->debit_total_vat) && isset($model->credit_total_vat)) {
                $model->balance_vat = $model->debit_total_vat - $model->credit_total_vat;
            }
        });

        static::updating(function ($model) {
            // Update updated_by
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }

            // Optional: recalc balances on update
            if (isset($model->debit_total) && isset($model->credit_total)) {
                $model->balance = $model->debit_total - $model->credit_total;
            }

            if (isset($model->debit_total_vat) && isset($model->credit_total_vat)) {
                $model->balance_vat = $model->debit_total_vat - $model->credit_total_vat;
            }
        });
    }
}