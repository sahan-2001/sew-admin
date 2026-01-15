<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ReleaseMaterial extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'site_id',
        'order_type',
        'order_id',
        'cutting_station_id',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    public function getWorkstationNameAttribute()
    {
        return $this->workstation ? $this->workstation->name : 'N/A';
    }
    
    public function cuttingStation()
    {
        return $this->belongsTo(CuttingStation::class, 'cutting_station_id');
    }

    public function lines()
    {
        return $this->hasMany(ReleaseMaterialLine::class, 'release_material_id');
    }

    public function deductStock(): void
    {
        $this->load('lines'); // Ensure the lines relationship is loaded

        foreach ($this->lines as $line) {
            // Find the stock entry for the item and location
            $stock = Stock::where('item_id', $line->item_id)
                ->where('location_id', $line->location_id)
                ->first();

            if (!$stock) {
                \Log::error('Stock deduction failed: No stock entry found for item_id ' . $line->item_id . ' and location_id ' . $line->location_id);
                continue;
            }

            // Deduct the released quantity from the stock
            $stock->quantity -= $line->quantity;

            // Ensure the stock quantity does not go below zero
            if ($stock->quantity < 0) {
                \Log::warning('Stock quantity below zero for item_id ' . $line->item_id . ' at location_id ' . $line->location_id);
                $stock->quantity = 0;
            }

            $stock->save();
            \Log::info('Stock updated: item_id=' . $line->item_id . ', location_id=' . $line->location_id . ', new quantity=' . $stock->quantity);
        }
    }

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
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('release_material')
            ->setDescriptionForEvent(function (string $eventName) {
                $user = auth()->user();
                $userInfo = $user ? " by {$user->name} (ID: {$user->id})" : "";
                return "ReleaseMaterial #{$this->id} has been {$eventName}{$userInfo}";
            });
    }
}
