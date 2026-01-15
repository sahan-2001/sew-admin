<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProductionMachine extends Model
{
    use LogsActivity;

    protected $fillable = [
        'site_id',
        'name',
        'description',
        'purchased_date',
        'start_working_date',
        'expected_lifetime',
        'purchased_cost',
        'additional_cost',
        'additional_cost_description',
        'total_initial_cost',
        'depreciation_rate',
        'depreciation_calculated_from',
        'last_depreciation_calculated_date',
        'depreciation_last',
        'cumulative_depreciation',
        'net_present_value',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::creating(function ($machine) {
            // Calculate costs
            $machine->total_initial_cost = $machine->purchased_cost + ($machine->additional_cost ?? 0);
            $machine->net_present_value = $machine->total_initial_cost - ($machine->cumulative_depreciation ?? 0);

            // Audit fields
            if (auth()->check()) {
                $machine->created_by = auth()->id();
                $machine->updated_by = auth()->id();
            }

            // Optional: assign site_id if exists
            if (isset($machine->site_id) && session()->has('site_id')) {
                $machine->site_id = session('site_id');
            }

            // Optional: generate barcode if empty
            if (isset($machine->barcode_id) && empty($machine->barcode_id)) {
                $machine->barcode_id = uniqid('CUT');
            }
        });

        static::updating(function ($machine) {
            // Recalculate costs
            $machine->total_initial_cost = $machine->purchased_cost + ($machine->additional_cost ?? 0);
            $machine->net_present_value = $machine->total_initial_cost - ($machine->cumulative_depreciation ?? 0);

            // Update audit
            if (auth()->check()) {
                $machine->updated_by = auth()->id();
            }
        });
    }

    public function calculateDepreciation()
    {
        $baseDate = $this->depreciation_calculated_from === 'purchased_date'
            ? $this->purchased_date
            : $this->start_working_date;

        $this->depreciation_last = $this->total_initial_cost * $this->depreciation_rate;
        $this->last_depreciation_calculated_date = now();
        $this->cumulative_depreciation += $this->depreciation_last;
        $this->net_present_value = $this->total_initial_cost - $this->cumulative_depreciation;
        $this->save();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('production_machine')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = auth()->user();
                $userInfo = $user ? " by {$user->name} (ID: {$user->id})" : "";
                return "ProductionMachine #{$this->id} has been {$eventName}{$userInfo}";
            });
    }
}
