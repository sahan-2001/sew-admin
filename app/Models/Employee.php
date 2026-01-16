<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Employee extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'site_id',
        'employee_code',
        'first_name',
        'last_name',
        'full_name',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'address',
        'joined_date',
        'employment_type',
        'designation',
        'department',
        'basic_salary',
        'epf_etf_group_id',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'joined_date' => 'datetime',
        'date_of_birth' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {

            // Auto-generate employee code (EMP001, EMP002, ...)
            if (empty($model->employee_code)) {
                $lastEmployee = static::withTrashed()
                    ->where('employee_code', 'like', 'EMP%')
                    ->orderBy('id', 'desc')
                    ->first();

                $newNumber = $lastEmployee
                    ? ((int) str_replace('EMP', '', $lastEmployee->employee_code) + 1)
                    : 1;

                $model->employee_code = 'EMP' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
            }

            // Auto set full name
            $model->full_name = trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? ''));

            // Auto set site
            if (session()->has('site_id')) {
                $model->site_id = session('site_id');
            }

            // Audit fields
            if (auth()->check()) {
                $model->created_by = auth()->id();
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function ($model) {

            // Keep full name updated
            $model->full_name = trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? ''));

            // Audit field
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logOnly($this->fillable);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function epfEtfGroup()
    {
        return $this->belongsTo(EpfEtfGroup::class, 'epf_etf_group_id');
    }
}
