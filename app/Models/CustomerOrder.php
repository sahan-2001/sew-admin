<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CustomerOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $primaryKey = 'order_id';

    protected $fillable = [
        'site_id',
        'name',
        'wanted_delivery_date',
        'customer_id',
        'special_notes',
        'status',
        'grand_total',
        'remaining_balance',
        'random_code',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            // Set site_id from session
            if (session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            // Set created_by and updated_by
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }

            // Generate 16-digit random_code if the column exists
            if (isset($model->random_code)) {
                $model->random_code = '';
                for ($i = 0; $i < 16; $i++) {
                    $model->random_code .= mt_rand(0, 9);
                }
            }

            // Calculate balances if columns exist
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

        static::saved(function ($model) {
            $model->recalculateGrandTotal();
        });
    }

    public function recalculateGrandTotal()
    {
        $oldGrandTotal = $this->grand_total;
        $this->grand_total = $this->orderItems()->sum('total');
        
        if (is_null($this->remaining_balance) || $this->remaining_balance == $oldGrandTotal) {
            $this->remaining_balance = $this->grand_total;
        }
        
        $this->saveQuietly();
    }

    public function setRemainingBalanceToGrandTotal()
    {
        $this->remaining_balance = $this->grand_total;
        $this->saveQuietly();
    }

    public function setRemainingBalanceAttribute($value)
    {
        if (is_null($value) && !is_null($this->grand_total)) {
            $this->attributes['remaining_balance'] = $this->grand_total;
        } else {
            $this->attributes['remaining_balance'] = $value;
        }
    }

    // Mutator to update remaining_balance when grand_total changes
    public function setGrandTotalAttribute($value)
    {
        $this->attributes['grand_total'] = $value;
        
        // If remaining_balance is not set or is equal to the old grand_total,
        // update it to match the new grand_total
        if (!isset($this->attributes['remaining_balance']) || 
            $this->attributes['remaining_balance'] == $this->getOriginal('grand_total')) {
            $this->attributes['remaining_balance'] = $value;
        }
    }

    public function orderItems()
    {
        return $this->hasMany(CustomerOrderDescription::class, 'customer_order_id', 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function variationItems()
    {
        return $this->hasManyThrough(
            VariationItem::class,
            CustomerOrderDescription::class,
            'customer_order_id', // Foreign key on CustomerOrderDescription
            'order_description_id', // Foreign key on VariationItem
            'order_id', // Local key on CustomerOrder
            'order_description_id' // Local key on CustomerOrderDescription
        );
    }

    // Configure activity log options
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('customer_order')
            ->setDescriptionForEvent(fn(string $eventName) => "Customer order with ID {$this->order_id} has been {$eventName}");
    }
}