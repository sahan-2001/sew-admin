<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $primaryKey = 'customer_id';

    protected $fillable = [
        'site_id',
        'name',
        'shop_name',
        'address_line_1',
        'address_line_2',
        'city',
        'zip_code',
        'email',
        'phone_1',
        'phone_2',
        'remaining_balance',
        'customer_vat_group_id',
        'requested_by',
        'approved_by',
        'created_by',
        'updated_by',
    ];

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function vatGroup()
    {
        return $this->belongsTo(CustomerVatGroup::class, 'customer_vat_group_id');
    }

    protected static $logAttributes = [
        'name',
        'shop_name',
        'address',
        'email',
        'phone_1',
        'phone_2',
        'remaining_balance',
        'customer_vat_group_id',
        'requested_by',
        'approved_by',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            // Set site_id from session
            if (session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });

        static::created(function ($customer) {
            \App\Models\CustomerControlAccount::create([
                'customer_id' => $customer->customer_id,
                'receivable_account_id' => null,
                'sales_account_id' => null,
                'vat_output_account_id' => null,
                'bad_debt_expense_account_id' => null,
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

    public function supplierAdvanceInvoices()
    {
        return $this->hasMany(\App\Models\SupplierAdvanceInvoice::class, 'provider_id', 'customer_id')
            ->where('provider_type', 'customer');
    }

    public function purchaseOrderInvoices()
    {
        return $this->hasMany(\App\Models\PurchaseOrderInvoice::class, 'provider_id', 'customer_id')
            ->where('provider_type', 'customer');
    }

    public function customerAdvanceInvoices()
    {
        return $this->hasMany(\App\Models\CustomerAdvanceInvoice::class, 'customer_id');
    }


    protected static $logName = 'customer';

    /**
     * Get the options for activity logging.
     *
     * @return \Spatie\Activitylog\LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'shop_name',
                'address',
                'email',
                'phone_1',
                'phone_2',
                'remaining_balance',
                'requested_by',
                'approved_by',
            ])
            ->useLogName('customer')
            ->setDescriptionForEvent(fn(string $eventName) => "Customer {$this->customer_id} has been {$eventName}");
    }
}