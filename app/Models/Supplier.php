<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $primaryKey = 'supplier_id';

    protected $fillable = [
        'name',
        'shop_name',
        'address_line_1',
        'address_line_2',
        'city',
        'zip_code',
        'email',
        'phone_1',
        'phone_2',
        'outstanding_balance',
        'supplier_vat_group_id',
        'approved_by',
        'created_by',
        'updated_by',
    ];

    public function vatGroup()
    {
        return $this->belongsTo(SupplierVatGroup::class, 'supplier_vat_group_id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
            $model->updated_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });

        static::created(function ($supplier) {
            \App\Models\SupplierControlAccount::create([
                'chart_of_account_id' => 1, 
                'supplier_id' => $supplier->supplier_id,
                'payable_account_id' => null,
                'purchase_account_id' => null,
                'vat_input_account_id' => null,
                'purchase_discount_account_id' => null,
                'bad_debt_recovery_account_id' => null,
                'debit_total' => 0,
                'credit_total' => 0,
                'balance' => 0,
                'debit_total_vat' => 0,
                'credit_total_vat' => 0,
                'balance_vat' => 0,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });
    }

    protected static $logAttributes = [
        'name',
        'shop_name',
        'address',
        'email',
        'phone_1',
        'phone_2',
        'outstanding_balance',
        'supplier_vat_group_id',
        'approved_by',
    ];

    public function supplierAdvanceInvoices()
    {
        return $this->hasMany(SupplierAdvanceInvoice::class, 'provider_id')
            ->where('provider_type', 'supplier');
    }

    public function purchaseOrderInvoices()
    {
        return $this->hasMany(\App\Models\PurchaseOrderInvoice::class, 'provider_id')
                    ->where('provider_type', 'supplier');
    }

    public function thirdPartyServices()
    {
        return $this->hasMany(\App\Models\ThirdPartyService::class, 'supplier_id');
    }

    protected static $logName = 'supplier';

    /**
     * Get the options for activity logging.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('supplier')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = auth()->user();
                $userInfo = $user ? " by {$user->name} (ID: {$user->id})" : "";
                return "Supplier #{$this->supplier_id} was {$eventName}{$userInfo}";
            });
    }
}
