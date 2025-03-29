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

    protected $primaryKey = 'supplier_id'; // Set the primary key to 'supplier_id'

    protected $fillable = [
        'name',
        'shop_name',
        'address',
        'email',
        'phone_1',
        'phone_2',
        'outstanding_balance',
        'added_by',
        'approved_by',
    ];

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    protected static $logAttributes = [
        'name',
        'shop_name',
        'address',
        'email',
        'phone_1',
        'phone_2',
        'outstanding_balance',
        'added_by',
        'approved_by',
    ];

    protected static $logName = 'supplier';

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
                'outstanding_balance',
                'added_by',
                'approved_by',
            ])
            ->useLogName('supplier')
            ->setDescriptionForEvent(fn(string $eventName) => "Supplier {$this->supplier_id} has been {$eventName} by User {$this->added_by} ({$this->addedBy->email})");
    }
}