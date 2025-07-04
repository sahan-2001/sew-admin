<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Action;

class SampleOrder extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $primaryKey = 'order_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'customer_id',
        'wanted_delivery_date',
        'special_notes',
        'status',
        'added_by',
        'accepted_by',
        'grand_total',
        'remaining_balance',
        'confirmation_message',
        'rejected_by',
        'rejection_message',
        'random_code',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($sampleOrder) {
            $sampleOrder->random_code = '';
            for ($i = 0; $i < 16; $i++) {
                $sampleOrder->random_code .= mt_rand(0, 9);
            }

            $sampleOrder->created_by = auth()->id();
            $sampleOrder->updated_by = auth()->id();

            $sampleOrder->remaining_balance = $sampleOrder->grand_total ?? 0;
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });

        static::saved(function ($model) {
            $model->recalculateGrandTotal();
        });
    }

    public function setRemainingBalanceAttribute($value)
    {
        if (is_null($value) && !is_null($this->grand_total)) {
            $this->attributes['remaining_balance'] = $this->grand_total;
        } else {
            $this->attributes['remaining_balance'] = $value;
        }
    }

    public function setGrandTotalAttribute($value)
    {
        $this->attributes['grand_total'] = $value;
        

        if (!isset($this->attributes['remaining_balance']) || 
            $this->attributes['remaining_balance'] == $this->getOriginal('grand_total')) {
            $this->attributes['remaining_balance'] = $value;
        }
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(SampleOrderItem::class, 'sample_order_id', 'order_id');
    }

    public function variations()
    {
        return $this->hasManyThrough(SampleOrderVariation::class, SampleOrderItem::class, 'sample_order_id', 'sample_order_item_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function actions(): MorphMany
    {
        return $this->morphMany(Action::class, 'model');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('sample_order')
            ->setDescriptionForEvent(function (string $eventName) {
                $description = "Sample order with ID {$this->order_id} has been {$eventName}";

                if ($this->isDirty('status')) {
                    $description .= ". New status: {$this->status}";
                }

                return $description;
            });
    }

    public function recalculateGrandTotal()
    {
        $this->load('items');
        $oldGrandTotal = $this->grand_total;
        $this->grand_total = $this->items->sum('total');
        
        if (is_null($this->remaining_balance) || $this->remaining_balance == $oldGrandTotal) {
            $this->remaining_balance = $this->grand_total;
        }
        
        $this->saveQuietly();
    }

    public function resetRemainingBalance()
    {
        $this->remaining_balance = $this->grand_total;
        $this->saveQuietly();
    }

    public function isFullyPaid()
    {
        return $this->remaining_balance <= 0;
    }

    public function getPaidAmount()
    {
        return $this->grand_total - $this->remaining_balance;
    }
}